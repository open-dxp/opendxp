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

use OpenDxp\Tests\Support\Test\TestCase;
use OpenDxp\Video\Adapter\Ffmpeg;
use ReflectionProperty;

class FfmpegTest extends TestCase
{
    private function getVideoFilter(Ffmpeg $ffmpeg): array
    {
        $prop = new ReflectionProperty(Ffmpeg::class, 'videoFilter');

        return $prop->getValue($ffmpeg);
    }

    public function testScaleByWidthDefaultIsForceResize(): void
    {
        $ffmpeg = new Ffmpeg();
        $ffmpeg->scaleByWidth(500);

        $this->assertSame(['scale=500:trunc(ow/a/2)*2'], $this->getVideoFilter($ffmpeg));
    }

    public function testScaleByWidthNoForceResize(): void
    {
        $ffmpeg = new Ffmpeg();
        $ffmpeg->scaleByWidth(500, false);

        $this->assertSame(['scale=if(gte(iw\,500)\,500\,iw):trunc(ow/a/2)*2'], $this->getVideoFilter($ffmpeg));
    }

    public function testScaleByHeightDefaultIsForceResize(): void
    {
        $ffmpeg = new Ffmpeg();
        $ffmpeg->scaleByHeight(300);

        $this->assertSame(['scale=trunc(oh/(ih/iw)/2)*2:300'], $this->getVideoFilter($ffmpeg));
    }

    public function testScaleByHeightNoForceResize(): void
    {
        $ffmpeg = new Ffmpeg();
        $ffmpeg->scaleByHeight(300, false);

        $this->assertSame(['scale=trunc(oh/(ih/iw)/2)*2:if(gte(ih\,300)\,300\,ih)'], $this->getVideoFilter($ffmpeg));
    }
}
