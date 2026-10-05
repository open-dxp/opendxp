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

namespace OpenDxp\Tests\Feature\Element;

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
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\EventDispatcher\EventDispatcher;

// Symfony's TagAwareAdapter remembers tag versions for 150ms, so an invalidation needs longer.
const TAG_VERSIONS_EXPIRED = 200000;

function cacheKeyOf(DataObject $object): string
{
    return Service::getElementCacheTag('object', $object->getId());
}

/**
 * Writes the object into the persistent cache the way the request before this one would have. The
 * save cleared the object's tags, and the handler refuses to write a tag it cleared itself.
 */
function cachedByAnEarlierRequest(DataObject $object): string
{
    Cache::getHandler()->removeClearedTags(array_values($object->getCacheTags()));
    Cache::save($object, cacheKeyOf($object), [], null, 0, true);

    return cacheKeyOf($object);
}

function pool(): object
{
    return (new ReflectionProperty(CoreCacheHandler::class, 'pool'))->getValue(Cache::getHandler());
}

/**
 * Runs the work against a cache handler of its own, the way another worker on the same pool would.
 */
function asASiblingWorker(callable $work): void
{
    $handler = new ReflectionProperty(Cache::class, 'handler');
    $ours = $handler->getValue();

    $lock = new WriteLock(pool());
    $lock->setLogger(new NullLogger());

    $sibling = new CoreCacheHandler(pool(), $lock, new EventDispatcher());
    $sibling->setLogger(new NullLogger());
    $sibling->setHandleCli(true);
    $sibling->setForceImmediateWrite(true);

    $handler->setValue(null, $sibling);

    try {
        $work();
    } finally {
        $handler->setValue(null, $ours);
    }
}

beforeEach(function () {
    // The kernel is rebooted per test while Cache::$handler survives statically, so the facade
    // would point at a handler of a container that is gone.
    (new ReflectionProperty(Cache::class, 'handler'))->setValue(null, null);

    $this->cacheWasEnabled = Cache::isEnabled();
    Cache::enable();

    $this->handler = Cache::getHandler();
    $this->handledCli = $this->handler->getHandleCli();
    $this->wroteImmediately = $this->handler->getForceImmediateWrite();

    $this->handler->setHandleCli(true);
    $this->handler->setForceImmediateWrite(true);

    RuntimeCache::clear();
});

// Nothing empties the cache here: clearing it truncates its table, which would commit the
// transaction the test runs in. An orphaned entry points at an id that is never handed out again.
afterEach(function () {
    $this->handler->setHandleCli($this->handledCli);
    $this->handler->setForceImmediateWrite($this->wroteImmediately);

    if (!$this->cacheWasEnabled) {
        Cache::disable();
    }
});

it('serves no stale object that a failed batch left in the prefetch buffer', function () {

    $objects = [];

    for ($position = 0; $position < 5; $position++) {
        $objects[] = UnittestFactory::createOne(['input' => 'original-' . $position]);
    }

    foreach ($objects as $object) {
        cachedByAnEarlierRequest($object);
    }

    RuntimeCache::clear();

    // The third object fails while it is loaded, so the fourth never gets its turn and its
    // prefetched entry stays unconsumed.
    $failing = $objects[2]->getId();
    $orphaned = $objects[3]->getId();

    $listener = static function (DataObjectEvent $fired) use ($failing): void {
        if ($fired->getObject()->getId() === $failing) {
            throw new LogicException('the object could not be loaded');
        }
    };

    OpenDxp::getEventDispatcher()->addListener(DataObjectEvents::POST_LOAD, $listener);

    try {
        $listing = new Unittest\Listing();
        $listing->setCondition('input LIKE ?', ['original-%']);
        $listing->setOrderKey('oo_id');
        $listing->setOrder('asc');

        expect(fn () => $listing->load())->toThrow(LogicException::class, 'the object could not be loaded');
    } finally {
        OpenDxp::getEventDispatcher()->removeListener(DataObjectEvents::POST_LOAD, $listener);
    }

    asASiblingWorker(function () use ($orphaned): void {
        $object = DataObject::getById($orphaned, ['force' => true]);
        $object->setInput('written by another worker');
        $object->save();
    });

    usleep(TAG_VERSIONS_EXPIRED);
    RuntimeCache::clear();

    expect(DataObject::getById($orphaned)->getInput())->toBe('written by another worker');
});

it('drops only its own batch from the prefetch buffer', function () {

    // An object of another batch that is still running, say an outer listing whose post load
    // listener started the one below.
    $outer = UnittestFactory::createOne(['input' => 'outer-batch']);
    $outerKey = cachedByAnEarlierRequest($outer);

    $inner = UnittestFactory::createOne(['input' => 'inner-batch']);

    RuntimeCache::clear();
    Cache::prefetch([$outerKey]);

    // From here on only the prefetch buffer can serve the outer object.
    pool()->deleteItem($outerKey);

    $listing = new Unittest\Listing();
    $listing->setCondition('input = ?', ['inner-batch']);
    $listing->load();

    expect($listing->getObjects())
        ->toHaveCount(1)
        ->and(Cache::load($outerKey))
        ->not->toBeFalse();
});

it('drops an unconsumed prefetch entry when the services are reset', function () {

    $object = UnittestFactory::createOne(['input' => 'original']);
    $key = cachedByAnEarlierRequest($object);

    RuntimeCache::clear();

    expect(Cache::load($key))->not->toBeFalse();

    // Buffer the entry without consuming it, the way an aborted batch leaves it.
    Cache::prefetch([$key]);

    asASiblingWorker(function () use ($object): void {
        $updated = DataObject::getById($object->getId(), ['force' => true]);
        $updated->setInput('written by another worker');
        $updated->save();
    });

    usleep(TAG_VERSIONS_EXPIRED);

    // Between two messages a worker resets every service tagged kernel.reset.
    Container::get('services_resetter')->reset();
    RuntimeCache::clear();

    expect(DataObject::getById($object->getId())->getInput())->toBe('written by another worker');
});
