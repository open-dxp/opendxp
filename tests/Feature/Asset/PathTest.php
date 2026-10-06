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

namespace OpenDxp\Tests\Feature\Asset;

use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;

it('returns a frontend path a browser can reach for the asset', function () {
    $image = AssetImageFactory::createOne();

    $path = $image->getFrontendPath();

    expect($path)
        ->toMatch('@^(https?|data):@')
        ->toContain($image->getFullPath());
});

it('returns a frontend path a browser can reach for a thumbnail', function () {
    $image = AssetImageFactory::createOne();
    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(256)
        ->create();
    $thumbnail = $image->getThumbnail($config);

    $path = $thumbnail->getFrontendPath();

    expect($path)
        ->toMatch('@^(https?|data):@')
        ->toContain($thumbnail->getPath());
});
