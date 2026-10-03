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

use OpenDxp\Video\Adapter\Ffmpeg;
use ReflectionProperty;

it('builds the scale filter ffmpeg is called with', function (string $scale, array $arguments, string $expected) {

    $ffmpeg = new Ffmpeg();
    $ffmpeg->{$scale}(...$arguments);

    expect((new ReflectionProperty(Ffmpeg::class, 'videoFilter'))->getValue($ffmpeg))->toBe([$expected]);
})->with([
    'a width it resizes to' => ['scaleByWidth', [500], 'scale=500:trunc(ow/a/2)*2'],
    'a width it only shrinks to' => ['scaleByWidth', [500, false], 'scale=if(gte(iw\,500)\,500\,iw):trunc(ow/a/2)*2'],
    'a height it resizes to' => ['scaleByHeight', [300], 'scale=trunc(oh/(ih/iw)/2)*2:300'],
    'a height it only shrinks to' => ['scaleByHeight', [300, false], 'scale=trunc(oh/(ih/iw)/2)*2:if(gte(ih\,300)\,300\,ih)'],
]);
