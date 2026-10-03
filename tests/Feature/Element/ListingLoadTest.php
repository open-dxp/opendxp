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

use OpenDxp;
use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\Service;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function marked(string $marker, int $count): array
{
    $objects = [];

    for ($position = 0; $position < $count; $position++) {
        $objects[] = UnittestFactory::createOne(['input' => sprintf('%s_%d', $marker, $position)]);
    }

    RuntimeCache::clear();

    return $objects;
}

function listingOf(string $marker): Unittest\Listing
{
    $listing = new Unittest\Listing();
    $listing->setCondition('input LIKE ?', [$marker . '%']);
    $listing->setOrderKey('oo_id');
    $listing->setOrder('asc');

    return $listing;
}

function idsOf(array $elements): array
{
    return array_map(static fn (object $element) => $element->getId(), $elements);
}

/**
 * Puts an element into the persistent cache the way a previous request would have. A save blocks
 * re-caching within the same process, so the tags it cleared have to be forgotten first.
 */
function cachePersistently(Concrete ...$objects): void
{
    foreach ($objects as $object) {
        Cache::getHandler()->removeClearedTags(array_values($object->getCacheTags()));
        Cache::save($object, Service::getElementCacheTag('object', $object->getId()), [], null, 0, true);
    }

    RuntimeCache::clear();
}

function idsThatWereLoaded(callable $load): array
{
    $loaded = [];
    $listener = static function (DataObjectEvent $fired) use (&$loaded): void {
        $loaded[] = $fired->getObject()->getId();
    };

    OpenDxp::getEventDispatcher()->addListener(DataObjectEvents::POST_LOAD, $listener);

    try {
        $load();
    } finally {
        OpenDxp::getEventDispatcher()->removeListener(DataObjectEvents::POST_LOAD, $listener);
    }

    return $loaded;
}

it('hands back every object its condition matches', function () {

    $objects = marked('every_marker', 5);

    $loaded = listingOf('every_marker')->load();

    expect($loaded)
        ->toHaveCount(5)
        ->and(idsOf($loaded))
        ->toBe(idsOf($objects))
        ->and($loaded[0])
        ->toBeInstanceOf(Concrete::class)
        ->and($loaded[0]->getInput())
        ->toBe('every_marker_0');
});

it('hands the objects back in the order it listed their ids', function () {

    marked('order_marker', 4);
    $listing = listingOf('order_marker');

    expect(idsOf($listing->load()))->toBe($listing->loadIdList());
});

it('hands back the same objects whether anything was loaded before or not', function () {

    $objects = marked('runtime_marker', 3);

    $cold = idsOf(listingOf('runtime_marker')->load());
    $warm = idsOf(listingOf('runtime_marker')->load());

    expect($cold)
        ->toBe(idsOf($objects))
        ->and($warm)
        ->toBe($cold);
});

it('hands back assets as assets', function () {

    $assets = AssetImageFactory::createMany(3);
    RuntimeCache::clear();

    $loaded = (new Asset\Listing())->load();

    expect($loaded[0])
        ->toBeInstanceOf(Asset::class)
        ->and(idsOf($loaded))
        ->toContain(...idsOf($assets));
});

it('hands back documents as documents', function () {

    $documents = DocumentPageFactory::createMany(3);
    RuntimeCache::clear();

    $loaded = (new Document\Listing())->load();

    expect($loaded[0])
        ->toBeInstanceOf(Document::class)
        ->and(idsOf($loaded))
        ->toContain(...idsOf($documents));
});

it('hands the objects it loaded to the listing itself', function () {

    marked('set_marker', 2);
    $listing = listingOf('set_marker');

    $loaded = $listing->load();

    expect($listing->getObjects())->toBe($loaded);
});

describe('objects that are in the persistent cache', function () {

    beforeEach(function () {
        $this->cacheWasEnabled = Cache::isEnabled();
        Cache::enable();
        Cache::getHandler()->setHandleCli(true);
    });

    // Nothing empties the cache here: clearing it truncates its table, and that would commit the
    // transaction the test runs in. An orphaned entry points at an id that is never handed out again.
    afterEach(function () {
        if (!$this->cacheWasEnabled) {
            Cache::disable();
        }
    });

    it('fires the post load event once per object, in the order of the listing', function () {

        $objects = marked('cache_marker', 3);
        cachePersistently(...$objects);

        $loaded = [];
        $fired = idsThatWereLoaded(function () use (&$loaded): void {
            $loaded = listingOf('cache_marker')->load();
        });

        expect(idsOf($loaded))
            ->toBe(idsOf($objects))
            ->and($fired)
            ->toBe(idsOf($objects));
    });

    it('fires the post load event in that order even when only some of them are cached', function () {

        $objects = marked('mixed_marker', 4);
        cachePersistently($objects[0], $objects[2]);

        $loaded = [];
        $fired = idsThatWereLoaded(function () use (&$loaded): void {
            $loaded = listingOf('mixed_marker')->load();
        });

        expect(idsOf($loaded))
            ->toBe(idsOf($objects))
            ->and($fired)
            ->toBe(idsOf($objects));
    });
});
