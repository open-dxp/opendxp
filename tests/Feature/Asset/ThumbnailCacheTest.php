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

use OpenDxp\Bundle\CoreBundle\Controller\PublicServicesController;
use OpenDxp\Model\Asset\Image\Thumbnail;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;
use OpenDxp\Tool\Storage;
use Symfony\Component\HttpFoundation\Request;

function thumbnailIsCached(Thumbnail $thumbnail): bool
{
    $date = $thumbnail->getAsset()->getDao()->getCachedThumbnailModificationDate(
        $thumbnail->getConfig()->getName(),
        $thumbnail->getFilename(),
    );

    return $date !== null;
}

function serveThumbnail(Thumbnail $thumbnail): void
{
    $request = new Request(attributes: [
        'assetId' => $thumbnail->getAsset()->getId(),
        'thumbnailName' => $thumbnail->getConfig()->getName(),
        'filename' => $thumbnail->getFilename(),
        'type' => 'image',
        'prefix' => '',
    ]);

    (new PublicServicesController())->thumbnailAction($request);
}

beforeEach(function () {
    $this->asset = AssetImageFactory::createOne([
        'data' => file_get_contents(fixture('image-large.jpg')),
    ]);
    // Thumbnails live outside the transaction. A run with the same faker seed finds the files of the last one.
    $this->asset->clearThumbnails(force: true);

    $config = ThumbnailConfigFactory::new()
        ->scalingByWidth(256)
        ->create();
    $this->thumbnail = $this->asset->getThumbnail($config);
    $this->storage = Storage::get('thumbnail');
    $this->path = $this->thumbnail->getPathReference(deferredAllowed: true)['storagePath'];
});

it('writes the thumbnail and caches its date once it is generated', function () {
    $this->thumbnail->getPath(['deferredAllowed' => false]);

    expect($this->storage->fileExists($this->path))
        ->toBeTrue()
        ->and(thumbnailIsCached($this->thumbnail))
        ->toBeTrue();
});

it('forgets the thumbnail when the asset changes', function () {
    $this->thumbnail->getPath(['deferredAllowed' => false]);

    $this->asset->setData(file_get_contents(AssetImageFactory::fixture()));
    $this->asset->save();

    expect($this->storage->fileExists($this->path))
        ->toBeFalse()
        ->and(thumbnailIsCached($this->thumbnail))
        ->toBeFalse();
});

it('writes the thumbnail and caches its date when it is served to a visitor', function () {
    serveThumbnail($this->thumbnail);

    expect($this->storage->fileExists($this->path))
        ->toBeTrue()
        ->and(thumbnailIsCached($this->thumbnail))
        ->toBeTrue();
});

it('keeps the cached date when only the file is deleted', function () {
    serveThumbnail($this->thumbnail);

    $this->storage->delete($this->path);

    expect($this->storage->fileExists($this->path))
        ->toBeFalse()
        ->and(thumbnailIsCached($this->thumbnail))
        ->toBeTrue();
});

it('writes the thumbnail again when a visitor asks for the deleted file', function () {
    serveThumbnail($this->thumbnail);
    $this->storage->delete($this->path);

    serveThumbnail($this->thumbnail);

    expect($this->storage->fileExists($this->path))
        ->toBeTrue()
        ->and(thumbnailIsCached($this->thumbnail))
        ->toBeTrue();
});
