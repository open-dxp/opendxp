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

namespace OpenDxp\Tests\Unit\Video\Adapter;

use Closure;
use OpenDxp\Tests\Application\Video\MockFfmpeg;
use OpenDxp\Video\Adapter\Ffmpeg;

it('builds the scale filter ffmpeg is called with', function (Closure $scale, string $filter) {
    $ffmpeg = new MockFfmpeg();

    $scale($ffmpeg);

    expect($ffmpeg->videoFilter())->toBe([$filter]);
})->with([
    'a width it resizes to' => [
        fn (Ffmpeg $ffmpeg) => $ffmpeg->scaleByWidth(500),
        'scale=500:trunc(ow/a/2)*2',
    ],
    'a width it only shrinks to' => [
        fn (Ffmpeg $ffmpeg) => $ffmpeg->scaleByWidth(500, forceResize: false),
        'scale=if(gte(iw\,500)\,500\,iw):trunc(ow/a/2)*2',
    ],
    'a height it resizes to' => [
        fn (Ffmpeg $ffmpeg) => $ffmpeg->scaleByHeight(300),
        'scale=trunc(oh/(ih/iw)/2)*2:300',
    ],
    'a height it only shrinks to' => [
        fn (Ffmpeg $ffmpeg) => $ffmpeg->scaleByHeight(300, forceResize: false),
        'scale=trunc(oh/(ih/iw)/2)*2:if(gte(ih\,300)\,300\,ih)',
    ],
]);
