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

use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\Service;
use OpenDxp\Test\Factory\AssetImageFactory;

beforeEach(fn () => $this->useApplicationCache());

it('hands a cached element back', function () {

    $asset = AssetImageFactory::createOne(['customSettings' => ['storeMarker' => 'persisted-value']]);
    $key = Service::getElementCacheTag('asset', $asset->getId());

    $this->allowCachingAgain($asset);

    expect(Cache::save($asset, $key, [], null, 9999, true))->toBeTrue();

    RuntimeCache::clear();

    expect(Cache::load($key))
        ->toBeInstanceOf(Asset::class)
        ->and(Cache::load($key)->getId())
        ->toBe($asset->getId())
        ->and(Cache::load($key)->getCustomSetting('storeMarker'))
        ->toBe('persisted-value');
});

it('hands back a copy, so a later change to the original stays out', function () {

    $asset = AssetImageFactory::createOne(['customSettings' => ['storeMarker' => 'original']]);
    $key = Service::getElementCacheTag('asset', $asset->getId());

    $this->allowCachingAgain($asset);
    Cache::save($asset, $key, [], null, 9999, true);

    $asset->setCustomSetting('storeMarker', 'changed-after-caching');
    RuntimeCache::clear();
    $loaded = Cache::load($key);

    expect($loaded)
        ->toBeInstanceOf(Asset::class)
        ->and($loaded)
        ->not->toBe($asset)
        ->and($loaded->getCustomSetting('storeMarker'))
        ->toBe('original');
});

it('caches what the database holds, not what an outdated handle holds', function () {

    $asset = AssetImageFactory::createOne(['customSettings' => ['storeMarker' => 'first']]);
    $key = Service::getElementCacheTag('asset', $asset->getId());

    $fresh = Asset::getById($asset->getId(), ['force' => true]);
    $fresh->setCustomSetting('storeMarker', 'second');
    $fresh->save();

    $this->allowCachingAgain($asset);
    Cache::save($asset, $key, [], null, 9999, true);

    RuntimeCache::clear();

    expect(Cache::load($key)->getCustomSetting('storeMarker'))->toBe('second');
});

it('reads an element through the cache layer', function () {

    $asset = AssetImageFactory::createOne();

    RuntimeCache::clear();

    expect(Service::getElementById('asset', $asset->getId()))
        ->toBeInstanceOf(Asset::class)
        ->and(Service::getElementById('asset', $asset->getId())->getId())
        ->toBe($asset->getId());
});
