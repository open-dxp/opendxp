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
use OpenDxp\Test\Factory\AssetImageFactory;

beforeEach(fn () => useApplicationCache());

it('returns a cached element', function () {
    $asset = AssetImageFactory::createOne([
        'customSettings' => ['marker' => 'cached'],
    ]);
    $key = cacheAsAnEarlierRequest($asset);
    RuntimeCache::clear();

    $loaded = Cache::load($key);

    expect($loaded)
        ->getId()
        ->toBe($asset->getId())
        ->getCustomSetting('marker')
        ->toBe('cached');
});

it('returns a copy that a later change to the original does not reach', function () {
    $asset = AssetImageFactory::createOne([
        'customSettings' => ['marker' => 'cached'],
    ]);
    $key = cacheAsAnEarlierRequest($asset);
    $asset->setCustomSetting('marker', 'changed');
    RuntimeCache::clear();

    $loaded = Cache::load($key);

    expect($loaded)
        ->not->toBe($asset)
        ->getCustomSetting('marker')
        ->toBe('cached');
});

it('caches the stored element instead of an outdated copy', function () {
    $outdated = AssetImageFactory::createOne([
        'customSettings' => ['marker' => 'outdated'],
    ]);
    $current = reloaded($outdated);
    $current->setCustomSetting('marker', 'current');
    $current->save();

    $key = cacheAsAnEarlierRequest($outdated);
    RuntimeCache::clear();

    expect(Cache::load($key))
        ->getCustomSetting('marker')
        ->toBe('current');
});
