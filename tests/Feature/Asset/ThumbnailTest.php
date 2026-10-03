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
use OpenDxp\Tool\Storage;

const WIDTH = 1024;
const HEIGHT = 768;

beforeEach(function () {
    $this->image = AssetImageFactory::createOne([
        'data' => file_get_contents(AssetImageFactory::fixture('image-large.jpg')),
    ]);
});

it('knows the size of the image it holds', function () {
    expect($this->image->getWidth())
        ->toBe(WIDTH)
        ->and($this->image->getHeight())
        ->toBe(HEIGHT);
});

it('turns the image on its side when the thumbnail rotates it by a quarter', function () {

    $config = ThumbnailConfigFactory::new()->rotating(90)->create();

    $thumbnail = $this->image->getThumbnail($config->getName(), false);

    expect($thumbnail->getWidth())
        ->toBe(HEIGHT)
        ->and($thumbnail->getHeight())
        ->toBe(WIDTH);
});

it('needs more room for a thumbnail rotated off the axis', function () {

    $config = ThumbnailConfigFactory::new()->rotating(45)->create();

    $thumbnail = $this->image->getThumbnail($config->getName(), false);

    expect($thumbnail->getWidth())
        ->toBeGreaterThan(WIDTH)
        ->and($thumbnail->getHeight())
        ->toBeGreaterThan(HEIGHT);
});

it('shrinks a thumbnail to the width asked for and keeps the ratio', function () {

    $config = ThumbnailConfigFactory::new()->scalingByWidth(256)->create();

    $thumbnail = $this->image->getThumbnail($config->getName(), false);

    expect($thumbnail->getWidth())
        ->toBe(256)
        ->and($thumbnail->getHeight())
        ->toBe(192);
});

it('writes the thumbnail it shrank, smaller than the image itself', function () {

    $config = ThumbnailConfigFactory::new()->scalingByWidth(256)->create();
    $thumbnail = $this->image->getThumbnail($config->getName(), false);

    $reference = $thumbnail->getPathReference(false);
    $stream = Storage::get($reference['type'])->readStream($reference['src']);

    expect($stream)
        ->toBeResource()
        ->and(strlen(stream_get_contents($stream)))
        ->toBeLessThan(strlen($this->image->getData()))
        ->and(getimagesize($thumbnail->getLocalFile()))
        ->toMatchArray([0 => 256, 1 => 192]);
});

it('leaves an image alone that is smaller than the thumbnail asks for', function () {

    $config = ThumbnailConfigFactory::new()->scalingByWidth(2048)->create();

    $thumbnail = $this->image->getThumbnail($config->getName(), false);

    expect($thumbnail->getWidth())
        ->toBe(WIDTH)
        ->and($thumbnail->getHeight())
        ->toBe(HEIGHT);
});

it('blows an image up when the thumbnail insists on the width', function () {

    $config = ThumbnailConfigFactory::new()->scalingByWidth(2048, forceResize: true)->create();

    $thumbnail = $this->image->getThumbnail($config->getName(), false);

    expect($thumbnail->getWidth())
        ->toBe(2048)
        ->and($thumbnail->getHeight())
        ->toBe(1536);
});

it('hands the thumbnail back in the format it is asked for', function (string $format) {

    $config = ThumbnailConfigFactory::new()->scalingByWidth(256)->create();
    $thumbnail = $this->image->getThumbnail($config->getName(), false);

    expect($thumbnail->getAsFormat($format)->getPath())->toEndWith('.' . $format);
})->with(['webp', 'jpg', 'png']);

it('loses the thumbnails it wrote once they are cleared', function () {

    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(256)
        ->create();
    $thumbnail = $this->image->getThumbnail($config->getName(), false);
    $storagePath = $thumbnail->getPathReference(true)['storagePath'];

    expect(Storage::get('thumbnail')->fileExists($storagePath))
        ->toBeTrue();

    $this->image->clearThumbnails(true);

    expect(Storage::get('thumbnail')->fileExists($storagePath))
        ->toBeFalse();
});
