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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Feature\Cache;

use LogicException;
use OpenDxp;
use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Model\DataObject;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;
use ReflectionProperty;

// Symfony's TagAwareAdapter remembers tag versions for 150ms, so an invalidation needs longer.
const TAG_VERSIONS_EXPIRED = 200000;

/**
 * Runs the work with a cache handler of its own on the same pool, the way another worker would.
 */
function asAnotherWorker(callable $work): void
{
    $handler = new ReflectionProperty(Cache::class, 'handler');
    $ours = $handler->getValue();

    $theirs = cacheHandler(Container::get('opendxp.cache.pool'));
    $theirs->setForceImmediateWrite(true);
    $handler->setValue(null, $theirs);

    try {
        $work();
    } finally {
        $handler->setValue(null, $ours);
    }
}

function changedByAnotherWorker(DataObject $object): void
{
    asAnotherWorker(static function () use ($object): void {
        $current = reloaded($object);
        $current->setInput('changed by another worker');
        $current->save();
    });

    usleep(TAG_VERSIONS_EXPIRED);
}

function loadedThroughTheCache(DataObject $object): DataObject
{
    RuntimeCache::clear();

    return DataObject::getById($object->getId());
}

function loadFailingAt(DataObject\Listing $listing, DataObject $failing): ?LogicException
{
    $listener = static function (DataObjectEvent $event) use ($failing): void {
        if ($event->getObject()->getId() === $failing->getId()) {
            throw new LogicException('The object could not be loaded.');
        }
    };
    $dispatcher = OpenDxp::getEventDispatcher();
    $dispatcher->addListener(DataObjectEvents::POST_LOAD, $listener);

    try {
        $listing->load();
    } catch (LogicException $failure) {
        return $failure;
    } finally {
        $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $listener);
    }

    return null;
}

beforeEach(function () {
    useApplicationCache();
    Cache::setForceImmediateWrite(true);
});

it('serves no stale object that a failed batch left in the prefetch buffer', function () {
    $objects = UnittestFactory::createMany(
        4,
        fn (int $index) => ['input' => sprintf('batch_%d', $index)],
    );
    foreach ($objects as $object) {
        cacheAsAnEarlierRequest($object);
    }
    RuntimeCache::clear();
    // The third object fails while it is loaded. The fourth never gets its turn, so its prefetched entry stays.
    $failing = $objects[2];
    $orphaned = $objects[3];
    $failure = loadFailingAt(unittestListing('batch_'), $failing);
    changedByAnotherWorker($orphaned);

    $loaded = loadedThroughTheCache($orphaned);

    expect($loaded->getInput())
        ->toBe('changed by another worker')
        ->and($failure)
        ->toBeInstanceOf(LogicException::class);
});

it('drops only its own batch from the prefetch buffer', function () {
    // The outer object belongs to a batch that is still running.
    $outer = UnittestFactory::createOne(['input' => 'outer']);
    $outerKey = cacheAsAnEarlierRequest($outer);
    UnittestFactory::createOne(['input' => 'inner']);
    RuntimeCache::clear();
    Cache::prefetch([$outerKey]);
    // From here on only the prefetch buffer can serve the outer object.
    Container::get('opendxp.cache.pool')->deleteItem($outerKey);

    unittestListing('inner')->load();

    expect(Cache::load($outerKey))
        ->getInput()
        ->toBe('outer');
});

it('drops an unconsumed prefetch entry when the services are reset', function () {
    $object = UnittestFactory::createOne();
    $key = cacheAsAnEarlierRequest($object);
    RuntimeCache::clear();
    Cache::prefetch([$key]);
    changedByAnotherWorker($object);

    resetServices();

    expect(loadedThroughTheCache($object))
        ->getInput()
        ->toBe('changed by another worker');
});
