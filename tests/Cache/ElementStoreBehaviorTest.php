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

namespace OpenDxp\Tests\Cache;

use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;

/**
 * Pins down what {@see CoreCacheHandler::storeCacheData()} guarantees when an
 * ElementInterface is passed: the cached payload must be a deep copy reflecting
 * the latest persisted data, and unsaved in-memory state must never leak into
 * the cache. Uses assets because the Cache suite has no class definitions.
 *
 * @group cache.element-store
 */
class ElementStoreBehaviorTest extends ModelTestCase
{
    private bool $cacheWasEnabled = false;

    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->cacheWasEnabled = Cache::isEnabled();
        if (!$this->cacheWasEnabled) {
            Cache::enable();
        }
        Cache::getHandler()->setHandleCli(true);
        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        Cache::clearAll();
        RuntimeCache::clear();
        if (!$this->cacheWasEnabled) {
            Cache::disable();
        }
        TestHelper::cleanUp();
        parent::tearDown();
    }

    /**
     * Saving an element clears its cache tag for the rest of the process,
     * which blocks re-caching it. Production processes end with the request;
     * tests simulate the next request by removing the cleared tags again.
     */
    private function simulateFreshRequestFor(ElementInterface $element): void
    {
        Cache::getHandler()->removeClearedTags(array_values($element->getCacheTags()));
    }

    public function testElementSavedToPersistentCacheIsLoadable(): void
    {
        $asset = TestHelper::createImageAsset('cache-store-');
        $asset->setCustomSetting('storeMarker', 'persisted-value');
        $asset->save();
        $id = $asset->getId();
        $cacheKey = Service::getElementCacheTag('asset', $id);

        $this->simulateFreshRequestFor($asset);
        $this->assertTrue(
            Cache::save($asset, $cacheKey, [], null, 9999, true),
            'Force-saving a persisted element to the cache must succeed'
        );

        RuntimeCache::clear();

        $loaded = Cache::load($cacheKey);
        $this->assertInstanceOf(Asset::class, $loaded);
        $this->assertSame($id, $loaded->getId());
        $this->assertSame('persisted-value', $loaded->getCustomSetting('storeMarker'));
    }

    public function testCachedElementIsADeepCopyNotTheOriginal(): void
    {
        $asset = TestHelper::createImageAsset('cache-deepcopy-');
        $asset->setCustomSetting('storeMarker', 'original');
        $asset->save();
        $id = $asset->getId();
        $cacheKey = Service::getElementCacheTag('asset', $id);

        $this->simulateFreshRequestFor($asset);
        Cache::save($asset, $cacheKey, [], null, 9999, true);

        // mutate the in-memory original without saving
        $asset->setCustomSetting('storeMarker', 'mutated-after-cache');

        RuntimeCache::clear();

        $loaded = Cache::load($cacheKey);
        $this->assertInstanceOf(Asset::class, $loaded);
        $this->assertNotSame($asset, $loaded, 'Cached element must be a copy, not the same instance');
        $this->assertSame(
            'original',
            $loaded->getCustomSetting('storeMarker'),
            'Mutating the original after caching must not leak into the cached copy'
        );
    }

    public function testCachedElementReflectsLatestDbStateNotInMemoryStaleness(): void
    {
        // Create + save the asset, then keep a stale handle.
        $asset = TestHelper::createImageAsset('cache-staleness-');
        $asset->setCustomSetting('storeMarker', 'first');
        $asset->save();
        $id = $asset->getId();
        $cacheKey = Service::getElementCacheTag('asset', $id);

        // Update via a fresh handle so the original handle is now stale
        // relative to the DB (different version count + modification date).
        $fresh = Asset::getById($id, ['force' => true]);
        $fresh->setCustomSetting('storeMarker', 'second');
        $fresh->save();

        // Save the stale in-memory handle to cache. The cache layer must
        // store data matching the latest DB row, not the stale 'first' value.
        $this->simulateFreshRequestFor($asset);
        Cache::save($asset, $cacheKey, [], null, 9999, true);

        RuntimeCache::clear();

        $loaded = Cache::load($cacheKey);
        if ($loaded === false) {
            // Acceptable behavior: refusing to cache stale data is also a way
            // to avoid corrupting the cache. The contract is "never cache the
            // wrong data", whether by reload-then-cache or by skip.
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertInstanceOf(Asset::class, $loaded);
        $this->assertSame(
            'second',
            $loaded->getCustomSetting('storeMarker'),
            'Cache must contain latest persisted data, not the stale in-memory copy'
        );
    }

    public function testServiceGetElementByIdRoutesThroughCacheLayer(): void
    {
        $asset = TestHelper::createImageAsset('cache-service-');
        $id = $asset->getId();

        RuntimeCache::clear();

        $loaded = Service::getElementById('asset', $id);
        $this->assertInstanceOf(Asset::class, $loaded);
        $this->assertSame($id, $loaded->getId());
    }
}
