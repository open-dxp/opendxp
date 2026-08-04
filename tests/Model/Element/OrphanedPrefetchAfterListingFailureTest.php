<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Model\Element;

use LogicException;
use OpenDxp;
use OpenDxp\Cache;
use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Model\Element\Service;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Prefetched cache entries that are never consumed (because a listing load
 * aborted between prefetch() and the individual getById() calls) must not
 * survive and serve stale data to later, unrelated reads in the same
 * long-running process (e.g. a Messenger worker).
 *
 * Scenario and test design contributed by @solverat in the review of the
 * batch prefetch optimization.
 *
 * @group cache.core.prefetch-buffer
 */
class OrphanedPrefetchAfterListingFailureTest extends ModelTestCase
{
    private bool $cacheWasEnabled = false;

    private bool $handleCliWasEnabled = false;

    private bool $forceImmediateWriteWasEnabled = false;

    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        // Codeception reboots the kernel between tests while Cache::$handler
        // survives statically, so it can point at an instance from a previous
        // container. A real worker process has exactly one container; force a
        // re-fetch so the container-driven kernel.reset acts on the same
        // instance the Cache facade uses.
        (new ReflectionProperty(Cache::class, 'handler'))->setValue(null, null);

        $this->cacheWasEnabled = Cache::isEnabled();
        if (!$this->cacheWasEnabled) {
            Cache::enable();
        }
        $this->handleCliWasEnabled = Cache::getHandler()->getHandleCli();
        $this->forceImmediateWriteWasEnabled = Cache::getHandler()->getForceImmediateWrite();
        Cache::getHandler()->setHandleCli(true);
        Cache::getHandler()->setForceImmediateWrite(true);

        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        Cache::clearAll();
        RuntimeCache::clear();
        Cache::getHandler()->setHandleCli($this->handleCliWasEnabled);
        Cache::getHandler()->setForceImmediateWrite($this->forceImmediateWriteWasEnabled);
        if (!$this->cacheWasEnabled) {
            Cache::disable();
        }
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testObjectSkippedByAFailedListingBatchDoesNotServeStaleDataToALaterRead(): void
    {
        $objects = [];
        for ($i = 0; $i < 5; $i++) {
            $obj = TestHelper::createEmptyObject('prefetch-orphan-');
            $obj->setInput('original-' . $i);
            $obj->save();
            $objects[] = $obj;
        }

        // put the objects into the persistent cache like a previous request
        // would have (forced, so state left behind by other tests such as a
        // previous Cache::clearAll() cannot silently skip the writes)
        foreach ($objects as $obj) {
            $this->simulateFreshRequestFor($obj);
            $cacheKey = Service::getElementCacheTag('object', $obj->getId());
            $this->assertTrue(
                Cache::save($obj, $cacheKey, [], null, 0, true),
                'Objects must be storable in the persistent cache for this scenario'
            );
        }

        // clear it, so the listing below actually goes through prefetch() and getById()
        RuntimeCache::clear();

        // object 3's POST_LOAD fails; object 4 never gets its own turn and
        // its prefetched entry stays unconsumed
        $failingId = $objects[2]->getId();
        $orphanedId = $objects[3]->getId();

        $failureMessage = 'simulated POST_LOAD failure for id ' . $failingId;

        $dispatcher = OpenDxp::getEventDispatcher();
        $listener = function (DataObjectEvent $event) use ($failingId, $failureMessage): void {
            if ($event->getObject()->getId() === $failingId) {
                throw new LogicException($failureMessage);
            }
        };

        $dispatcher->addListener(DataObjectEvents::POST_LOAD, $listener);

        // batch job: the caller catches the failure so one bad object does
        // not kill the worker
        try {
            $listing = new Unittest\Listing();
            $listing->setCondition("input LIKE 'original-%'");
            $listing->setOrderKey('oo_id');
            $listing->setOrder('asc');
            $listing->load();
            $this->fail('expected the simulated POST_LOAD failure');
        } catch (LogicException $e) {
            $this->assertSame($failureMessage, $e->getMessage());
        } finally {
            $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $listener);
        }

        // a sibling worker updates the object whose prefetched entry was
        // never consumed
        $this->withSiblingWorkerHandler(function () use ($orphanedId): void {
            $updated = DataObject::getById($orphanedId, ['force' => true]);
            $updated->setInput('updated-by-sibling-worker');
            $updated->save();
        });

        // save() invalidates the cache tag, but Symfony TagAwareAdapter
        // caches tag versions locally for 150ms (knownTagVersionsTtl)
        usleep(200000);

        // a new message starts: the worker clears the RuntimeCache
        RuntimeCache::clear();

        $reloaded = DataObject::getById($orphanedId);

        $this->assertSame(
            'updated-by-sibling-worker',
            $reloaded->getInput(),
            'got stale cached data instead of the sibling worker update: the prefetch buffer was not cleared'
        );
    }

    public function testListingLoadDropsOnlyItsOwnBatchFromThePrefetchBuffer(): void
    {
        // an object belonging to a different, still running batch, e.g. an
        // outer listing whose POST_LOAD listener triggered the load below
        $outer = TestHelper::createEmptyObject('prefetch-outer-');
        $outer->setInput('outer-batch');
        $outer->save();
        $this->simulateFreshRequestFor($outer);

        $outerKey = Service::getElementCacheTag('object', $outer->getId());
        $this->assertTrue(
            Cache::save($outer, $outerKey, [], null, 0, true),
            'The outer object must be storable in the persistent cache for this scenario'
        );

        $inner = TestHelper::createEmptyObject('prefetch-inner-');
        $inner->setInput('inner-batch');
        $inner->save();

        RuntimeCache::clear();

        Cache::prefetch([$outerKey]);

        // remove the pool entry behind the handler's back: from here on the
        // outer object can only be served by the prefetch buffer
        $poolProperty = new ReflectionProperty(CoreCacheHandler::class, 'pool');
        /** @var TagAwareAdapterInterface $pool */
        $pool = $poolProperty->getValue(Cache::getHandler());
        $pool->deleteItem($outerKey);

        $listing = new Unittest\Listing();
        $listing->setCondition("input = 'inner-batch'");
        $listing->load();
        $this->assertCount(1, $listing->getObjects(), 'The inner listing must load its own batch');

        $this->assertNotFalse(
            Cache::load($outerKey),
            'a listing load must only drop its own batch from the prefetch buffer, not entries of other batches'
        );
    }

    public function testFrameworkServiceResetClearsUnconsumedPrefetchEntries(): void
    {
        $obj = TestHelper::createEmptyObject('prefetch-reset-');
        $obj->setInput('original');
        $obj->save();
        $this->simulateFreshRequestFor($obj);

        $cacheKey = Service::getElementCacheTag('object', $obj->getId());
        $this->assertTrue(
            Cache::save($obj, $cacheKey, [], null, 0, true),
            'The object must be storable in the persistent cache for this scenario'
        );

        RuntimeCache::clear();

        // buffer the entry without consuming it, like an aborted batch would
        $this->assertNotFalse(Cache::load($cacheKey), 'The object must be a persistent cache hit before prefetching');
        Cache::prefetch([$cacheKey]);

        $this->withSiblingWorkerHandler(function () use ($obj): void {
            $updated = DataObject::getById($obj->getId(), ['force' => true]);
            $updated->setInput('updated-by-sibling-worker');
            $updated->save();
        });

        usleep(200000);

        // between two messages the Messenger worker resets all services
        // tagged with kernel.reset
        OpenDxp::getContainer()->get('services_resetter')->reset();
        RuntimeCache::clear();

        $reloaded = DataObject::getById($obj->getId());

        $this->assertSame(
            'updated-by-sibling-worker',
            $reloaded->getInput(),
            'kernel.reset must drop unconsumed prefetch entries so a new message cannot read stale data'
        );
    }

    private function simulateFreshRequestFor(DataObject $object): void
    {
        Cache::getHandler()->removeClearedTags(array_values($object->getCacheTags()));
    }

    /**
     * Runs $work with a fresh CoreCacheHandler on the same backend, standing
     * in for a different worker process. Needed because writing through our
     * own handler would clear the buffer as a side effect and hide the bug.
     */
    private function withSiblingWorkerHandler(callable $work): void
    {
        $poolProperty = new ReflectionProperty(CoreCacheHandler::class, 'pool');
        /** @var TagAwareAdapterInterface $pool */
        $pool = $poolProperty->getValue(Cache::getHandler());

        $handlerProperty = new ReflectionProperty(Cache::class, 'handler');
        $originalHandler = $handlerProperty->getValue();

        $writeLock = new WriteLock($pool);
        $writeLock->setLogger(new NullLogger());
        $siblingHandler = new CoreCacheHandler($pool, $writeLock, new EventDispatcher());
        $siblingHandler->setLogger(new NullLogger());
        $siblingHandler->setHandleCli(true);
        $siblingHandler->setForceImmediateWrite(true);

        $handlerProperty->setValue(null, $siblingHandler);

        try {
            $work();
        } finally {
            $handlerProperty->setValue(null, $originalHandler);
        }
    }
}
