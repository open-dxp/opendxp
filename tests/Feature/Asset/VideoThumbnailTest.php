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

use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Test\Factory\VideoThumbnailConfigFactory;

it('converts a video to the formats of its thumbnail', function () {
    $config = VideoThumbnailConfigFactory::new()
        ->scalingByWidth(120)
        ->create();
    $video = AssetVideoFactory::new()
        ->convertedTo($config->getName())
        ->create();

    $thumbnail = $video->getThumbnail($config->getName());

    expect($thumbnail)
        ->toHaveKey('status', 'finished')
        ->and($thumbnail['formats'])
        ->toHaveKey('mp4');
});
