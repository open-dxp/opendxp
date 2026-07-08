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

use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\Service;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;

/**
 * Pins down the behavior of the per-element listing DAOs (DataObject, Asset,
 * Document) that load full elements from an ID list. Any future batch
 * prefetch optimization must keep returning the same elements in the same
 * order, regardless of whether items are warm in the RuntimeCache.
 *
 * @group model.element.listing
 */
class ListingLoadTest extends ModelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        TestHelper::cleanUp();
        RuntimeCache::clear();
        parent::tearDown();
    }

    public function testDataObjectListingLoadReturnsAllMatchingObjects(): void
    {
        $created = [];
        for ($i = 0; $i < 5; $i++) {
            $obj = TestHelper::createEmptyObject('listing-load-do-');
            $obj->setInput('listing_load_marker_' . $i);
            $obj->save();
            $created[$obj->getId()] = $obj->getInput();
        }

        RuntimeCache::clear();

        $listing = new Unittest\Listing();
        $listing->setCondition("input LIKE 'listing_load_marker_%'");
        $listing->setOrderKey('oo_id');
        $listing->setOrder('asc');
        $loaded = $listing->load();

        $this->assertCount(count($created), $loaded);
        foreach ($loaded as $obj) {
            $this->assertInstanceOf(DataObject\Concrete::class, $obj);
            $this->assertArrayHasKey($obj->getId(), $created);
            $this->assertSame($created[$obj->getId()], $obj->getInput());
        }
    }

    public function testDataObjectListingLoadOrderMatchesIdList(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $obj = TestHelper::createEmptyObject('listing-load-order-');
            $obj->setInput('order_marker_' . $i);
            $obj->save();
        }

        RuntimeCache::clear();

        $listing = new Unittest\Listing();
        $listing->setCondition("input LIKE 'order_marker_%'");
        $listing->setOrderKey('oo_id');
        $listing->setOrder('asc');

        $idList = $listing->loadIdList();
        $loaded = $listing->load();

        $loadedIds = array_map(fn ($o) => $o->getId(), $loaded);
        $this->assertSame($idList, $loadedIds, 'load() must preserve the order returned by loadIdList()');
    }

    public function testDataObjectListingLoadIsConsistentWithRuntimeCacheState(): void
    {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $obj = TestHelper::createEmptyObject('listing-rt-');
            $obj->setInput('rt_marker_' . $i);
            $obj->save();
            $ids[] = $obj->getId();
        }

        // First listing call: nothing warm
        RuntimeCache::clear();

        $listing1 = new Unittest\Listing();
        $listing1->setCondition("input LIKE 'rt_marker_%'");
        $listing1->setOrderKey('oo_id');
        $listing1->setOrder('asc');
        $cold = $listing1->load();
        $coldIds = array_map(fn ($o) => $o->getId(), $cold);

        // Second listing call: everything warm
        $listing2 = new Unittest\Listing();
        $listing2->setCondition("input LIKE 'rt_marker_%'");
        $listing2->setOrderKey('oo_id');
        $listing2->setOrder('asc');
        $warm = $listing2->load();
        $warmIds = array_map(fn ($o) => $o->getId(), $warm);

        $this->assertSame($coldIds, $warmIds, 'Cold and warm listing loads must return the same IDs');
        $this->assertSame($ids, $coldIds);
    }

    public function testAssetListingLoadReturnsAssetInstances(): void
    {
        $created = [];
        for ($i = 0; $i < 3; $i++) {
            $asset = TestHelper::createImageAsset();
            $created[] = $asset->getId();
        }

        RuntimeCache::clear();

        $listing = new Asset\Listing();
        $loaded = $listing->load();

        $loadedIds = array_map(fn (Asset $a) => $a->getId(), $loaded);
        foreach ($loaded as $a) {
            $this->assertInstanceOf(Asset::class, $a);
        }
        foreach ($created as $id) {
            $this->assertContains($id, $loadedIds);
        }
    }

    public function testDocumentListingLoadReturnsDocumentInstances(): void
    {
        $created = [];
        for ($i = 0; $i < 3; $i++) {
            $doc = TestHelper::createEmptyDocumentPage('listing-load-doc-', true, true);
            $created[] = $doc->getId();
        }

        RuntimeCache::clear();

        $listing = new Document\Listing();
        $loaded = $listing->load();

        $loadedIds = array_map(fn (Document $d) => $d->getId(), $loaded);
        foreach ($loaded as $d) {
            $this->assertInstanceOf(Document::class, $d);
        }
        foreach ($created as $id) {
            $this->assertContains($id, $loadedIds);
        }
    }

    public function testListingLoadFromPersistentCacheDispatchesPostLoadPerElement(): void
    {
        $cacheWasEnabled = Cache::isEnabled();
        if (!$cacheWasEnabled) {
            Cache::enable();
        }
        Cache::getHandler()->setHandleCli(true);

        try {
            $ids = [];
            $created = [];
            for ($i = 0; $i < 3; $i++) {
                $obj = TestHelper::createEmptyObject('listing-cache-');
                $obj->setInput('cache_marker_' . $i);
                $obj->save();
                $ids[] = $obj->getId();
                $created[] = $obj;
            }

            // put the objects into the persistent cache like a previous
            // request would have (element saves block re-caching within the
            // same process, so the cleared tags have to be reset first)
            foreach ($created as $obj) {
                Cache::getHandler()->removeClearedTags(array_values($obj->getCacheTags()));
                $cacheKey = Service::getElementCacheTag('object', $obj->getId());
                $this->assertTrue(
                    Cache::save($obj, $cacheKey, [], null, 0, true),
                    'Objects must be storable in the persistent cache for this scenario'
                );
            }

            RuntimeCache::clear();

            $dispatcher = \OpenDxp::getEventDispatcher();
            $postLoadIds = [];
            $listener = function (DataObjectEvent $event) use (&$postLoadIds): void {
                $postLoadIds[] = $event->getObject()->getId();
            };
            $dispatcher->addListener(DataObjectEvents::POST_LOAD, $listener);

            try {
                $listing = new Unittest\Listing();
                $listing->setCondition("input LIKE 'cache_marker_%'");
                $listing->setOrderKey('oo_id');
                $listing->setOrder('asc');
                $loaded = $listing->load();
            } finally {
                $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $listener);
            }

            $this->assertSame($ids, array_map(fn ($o) => $o->getId(), $loaded));
            $this->assertSame(
                $ids,
                $postLoadIds,
                'POST_LOAD must fire exactly once per element, in listing order'
            );
        } finally {
            Cache::clearAll();
            if (!$cacheWasEnabled) {
                Cache::disable();
            }
        }
    }

    public function testListingLoadWithPartiallyCachedElementsKeepsEventOrder(): void
    {
        $cacheWasEnabled = Cache::isEnabled();
        if (!$cacheWasEnabled) {
            Cache::enable();
        }
        Cache::getHandler()->setHandleCli(true);

        try {
            $ids = [];
            $created = [];
            for ($i = 0; $i < 4; $i++) {
                $obj = TestHelper::createEmptyObject('listing-mixed-');
                $obj->setInput('mixed_marker_' . $i);
                $obj->save();
                $ids[] = $obj->getId();
                $created[] = $obj;
            }

            // cache only the 1st and 3rd element — the 2nd and 4th stay
            // uncached, so the listing has to interleave persistent-cache
            // hits and DB loads
            foreach ([$created[0], $created[2]] as $obj) {
                Cache::getHandler()->removeClearedTags(array_values($obj->getCacheTags()));
                $cacheKey = Service::getElementCacheTag('object', $obj->getId());
                $this->assertTrue(
                    Cache::save($obj, $cacheKey, [], null, 0, true),
                    'Objects must be storable in the persistent cache for this scenario'
                );
            }

            RuntimeCache::clear();

            $dispatcher = \OpenDxp::getEventDispatcher();
            $postLoadIds = [];
            $listener = function (DataObjectEvent $event) use (&$postLoadIds): void {
                $postLoadIds[] = $event->getObject()->getId();
            };
            $dispatcher->addListener(DataObjectEvents::POST_LOAD, $listener);

            try {
                $listing = new Unittest\Listing();
                $listing->setCondition("input LIKE 'mixed_marker_%'");
                $listing->setOrderKey('oo_id');
                $listing->setOrder('asc');
                $loaded = $listing->load();
            } finally {
                $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $listener);
            }

            $this->assertSame($ids, array_map(fn ($o) => $o->getId(), $loaded));
            $this->assertSame(
                $ids,
                $postLoadIds,
                'POST_LOAD must fire in listing order even when only some elements are cached'
            );
        } finally {
            Cache::clearAll();
            if (!$cacheWasEnabled) {
                Cache::disable();
            }
        }
    }

    public function testDataObjectListingLoadCallsSetObjectsOnModel(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $obj = TestHelper::createEmptyObject('listing-set-');
            $obj->setInput('set_marker_' . $i);
            $obj->save();
        }

        RuntimeCache::clear();

        $listing = new Unittest\Listing();
        $listing->setCondition("input LIKE 'set_marker_%'");
        $loaded = $listing->load();

        $this->assertSame($loaded, $listing->getObjects(), 'load() must populate the listing model with the same array');
    }
}
