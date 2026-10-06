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

beforeEach(function () {
    $this->image = AssetImageFactory::createOne([
        'data' => file_get_contents(fixture('image-large.jpg')),
    ]);
});

it('detects the size of the image', function () {
    expect($this->image)
        ->getWidth()
        ->toBe(1024)
        ->getHeight()
        ->toBe(768);
});

it('turns the image on its side when the thumbnail rotates it by a quarter', function () {
    $config = ThumbnailConfigFactory::new()
        ->rotating(90)
        ->create();

    $thumbnail = $this->image->getThumbnail($config, deferred: false);

    expect($thumbnail->getWidth())
        ->toBe(768)
        ->and($thumbnail->getHeight())
        ->toBe(1024);
});

it('needs more room for a thumbnail rotated off the axis', function () {
    $config = ThumbnailConfigFactory::new()
        ->rotating(45)
        ->create();

    $thumbnail = $this->image->getThumbnail($config, deferred: false);

    expect($thumbnail->getWidth())
        ->toBeGreaterThan(1024)
        ->and($thumbnail->getHeight())
        ->toBeGreaterThan(768);
});

it('shrinks a thumbnail to the width asked for and keeps the ratio', function () {
    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(256)
        ->create();

    $thumbnail = $this->image->getThumbnail($config, deferred: false);

    expect($thumbnail->getWidth())
        ->toBe(256)
        ->and($thumbnail->getHeight())
        ->toBe(192);
});

it('writes a shrunk thumbnail smaller than the image', function () {
    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(256)
        ->create();

    $thumbnail = $this->image->getThumbnail($config, deferred: false);

    expect($thumbnail->getFileSize())->toBeLessThan($this->image->getFileSize());
});

it('leaves an image alone that is smaller than the thumbnail asks for', function () {
    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(2048)
        ->create();

    $thumbnail = $this->image->getThumbnail($config, deferred: false);

    expect($thumbnail->getWidth())
        ->toBe(1024)
        ->and($thumbnail->getHeight())
        ->toBe(768);
});

it('enlarges an image when the thumbnail insists on the width', function () {
    $config = ThumbnailConfigFactory::new()
        ->enlargingToWidth(2048)
        ->create();

    $thumbnail = $this->image->getThumbnail($config, deferred: false);

    expect($thumbnail->getWidth())
        ->toBe(2048)
        ->and($thumbnail->getHeight())
        ->toBe(1536);
});

it('returns the thumbnail in the format it is asked for', function (string $format) {
    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(256)
        ->create();
    $thumbnail = $this->image->getThumbnail($config, deferred: false);

    $converted = $thumbnail->getAsFormat($format);

    expect($converted->getPath())->toEndWith(sprintf('.%s', $format));
})->with([
    'webp' => ['webp'],
    'jpeg' => ['jpg'],
    'png' => ['png'],
]);

it('deletes the thumbnails it wrote when they are cleared', function () {
    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(256)
        ->create();
    $thumbnail = $this->image->getThumbnail($config, deferred: false);
    $path = $thumbnail->getPathReference(deferredAllowed: true)['storagePath'];

    $this->image->clearThumbnails(force: true);

    expect(Storage::get('thumbnail')->fileExists($path))->toBeFalse();
});
