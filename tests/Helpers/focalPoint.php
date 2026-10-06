<?php

declare(strict_types=1);

use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Asset\Image\Thumbnail\Config;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;

function redAboveBlue(): Image
{
    $canvas = imagecreatetruecolor(100, 400);
    imagefilledrectangle($canvas, 0, 0, 99, 199, imagecolorallocate($canvas, 255, 0, 0));
    imagefilledrectangle($canvas, 0, 200, 99, 399, imagecolorallocate($canvas, 0, 0, 255));

    ob_start();
    imagepng($canvas);

    return AssetImageFactory::createOne([
        'filename' => sprintf('red-above-blue-%s.png', uniqid()),
        'data' => ob_get_clean(),
    ]);
}

function landscapeCover(): Config
{
    $config = ThumbnailConfigFactory::new()
        ->unsaved()
        ->create();
    $config->addItem('cover', [
        'width' => 200,
        'height' => 50,
        'positioning' => 'center',
        'forceResize' => true,
    ], 'default');
    $config->setFormat('PNG');
    $config->save();

    return $config;
}

function thumbnailColours(Image $image, Config $config): string
{
    $thumbnail = imagecreatefromstring((string) file_get_contents($image->getThumbnail($config)->getLocalFile()));
    $colourAt = static function (int $y) use ($thumbnail): string {
        $rgb = imagecolorsforindex(
            $thumbnail,
            imagecolorat($thumbnail, intdiv(imagesx($thumbnail), 2), $y),
        );

        return $rgb['red'] > $rgb['blue'] ? 'red' : 'blue';
    };

    return sprintf(
        '%s above %s',
        $colourAt(intdiv(imagesy($thumbnail), 4)),
        $colourAt(intdiv(imagesy($thumbnail) * 3, 4)),
    );
}

function focusOn(Image $image, float $x, float $y): void
{
    $image->setCustomSetting('focalPointX', $x);
    $image->setCustomSetting('focalPointY', $y);
    $image->save();
}
