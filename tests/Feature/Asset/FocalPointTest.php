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

use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Asset\Image\Thumbnail\Config;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;

function redAboveBlue(): Image
{
    $canvas = imagecreatetruecolor(100, 400);
    $red = imagecolorallocate($canvas, 255, 0, 0);
    $blue = imagecolorallocate($canvas, 0, 0, 255);
    imagefilledrectangle($canvas, 0, 0, 99, 199, $red);
    imagefilledrectangle($canvas, 0, 200, 99, 399, $blue);

    ob_start();
    imagepng($canvas);

    return AssetImageFactory::createOne([
        'filename' => sprintf('red-above-blue-%s.png', uniqid()),
        'data' => ob_get_clean(),
    ]);
}

function landscapeCover(): Config
{
    return ThumbnailConfigFactory::new()
        ->covering(200, 50)
        ->create(['format' => 'PNG']);
}

function thumbnailColours(Image $image, Config $config): string
{
    $file = $image->getThumbnail($config)->getLocalFile();
    $thumbnail = imagecreatefromstring((string) file_get_contents($file));
    $middle = intdiv(imagesx($thumbnail), 2);
    $colourAt = static function (int $y) use ($thumbnail, $middle): string {
        $colour = imagecolorat($thumbnail, $middle, $y);
        $rgb = imagecolorsforindex($thumbnail, $colour);

        return $rgb['red'] > $rgb['blue'] ? 'red' : 'blue';
    };
    $upper = intdiv(imagesy($thumbnail), 4);
    $lower = intdiv(imagesy($thumbnail) * 3, 4);

    return sprintf('%s above %s', $colourAt($upper), $colourAt($lower));
}

function focusOn(Image $image, float $x, float $y): void
{
    $image->setCustomSetting('focalPointX', $x);
    $image->setCustomSetting('focalPointY', $y);
    $image->save();
}

it('crops a cover thumbnail around a focal point on the left edge', function () {
    $image = redAboveBlue();
    $cover = landscapeCover();
    focusOn($image, 0.0, 90.0);

    $colours = thumbnailColours($image, $cover);

    expect($colours)->toBe('blue above blue');
});

it('crops the thumbnails again once the focal point moves', function () {
    $image = redAboveBlue();
    $cover = landscapeCover();
    focusOn($image, 50.0, 90.0);
    thumbnailColours($image, $cover);

    focusOn($image, 50.0, 10.0);

    expect(thumbnailColours($image, $cover))->toBe('red above red');
});

it('crops the thumbnails around the middle again once the focal point is removed', function () {
    $image = redAboveBlue();
    $cover = landscapeCover();
    focusOn($image, 50.0, 90.0);
    thumbnailColours($image, $cover);

    $image->removeCustomSetting('focalPointX');
    $image->removeCustomSetting('focalPointY');
    $image->save();

    expect(thumbnailColours($image, $cover))->toBe('red above blue');
});
