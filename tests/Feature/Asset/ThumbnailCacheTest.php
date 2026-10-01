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
use OpenDxp\Model\Asset;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;
use OpenDxp\Tool\Storage;
use Symfony\Component\HttpFoundation\Request;

function cachedDate(Asset $asset, string $thumbnail, string $filename): ?int
{
    return $asset->getDao()->getCachedThumbnailModificationDate($thumbnail, $filename);
}

function servedThumbnail(Asset $asset, string $thumbnail, string $filename): void
{
    (new PublicServicesController())->thumbnailAction(new Request(attributes: [
        'assetId' => $asset->getId(),
        'thumbnailName' => $thumbnail,
        'filename' => $filename,
        'type' => 'image',
        'prefix' => '',
    ]))->sendContent();
}

beforeEach(function () {
    $this->asset = AssetImageFactory::createOne([
        'data' => file_get_contents(AssetImageFactory::fixture('image-large.jpg')),
    ]);
    $this->name = ThumbnailConfigFactory::new()->scalingByWidth(256)->create()->getName();
    $this->storage = Storage::get('thumbnail');

    $this->asset->clearThumbnails(true);

    $this->thumbnail = $this->asset->getThumbnail($this->name);
    $this->path = $this->thumbnail->getPathReference(true)['storagePath'];

    $this->isCached = fn (): bool => cachedDate($this->asset, $this->name, $this->thumbnail->getFilename()) !== null;
    $this->exists = fn (): bool => $this->storage->fileExists($this->path);
});

it('caches nothing before a thumbnail was ever generated', function () {
    expect(($this->isCached)())
        ->toBeFalse()
        ->and(($this->exists)())
        ->toBeFalse();
});

it('writes the thumbnail and caches its date once it is generated', function () {

    $this->thumbnail->getPath(['deferredAllowed' => false]);

    expect(($this->exists)())
        ->toBeTrue()
        ->and(($this->isCached)())
        ->toBeTrue();
});

it('forgets the thumbnail when the asset itself changes', function () {

    $this->thumbnail->getPath(['deferredAllowed' => false]);

    $this->asset->setData(file_get_contents(AssetImageFactory::fixture()));
    $this->asset->save();

    expect(($this->isCached)())
        ->toBeFalse()
        ->and(($this->exists)())
        ->toBeFalse();
});

it('writes the thumbnail and caches its date when it is served to a visitor', function () {

    servedThumbnail($this->asset, $this->name, $this->thumbnail->getFilename());

    expect(($this->isCached)())
        ->toBeTrue()
        ->and(($this->exists)())
        ->toBeTrue();
});

it('keeps the cached date when only the file is deleted', function () {

    servedThumbnail($this->asset, $this->name, $this->thumbnail->getFilename());

    $this->storage->delete($this->path);

    expect(($this->isCached)())
        ->toBeTrue()
        ->and(($this->exists)())
        ->toBeFalse();
});

it('writes the thumbnail again when a visitor asks for the deleted file', function () {

    servedThumbnail($this->asset, $this->name, $this->thumbnail->getFilename());
    $this->storage->delete($this->path);

    servedThumbnail($this->asset, $this->name, $this->thumbnail->getFilename());

    expect(($this->isCached)())
        ->toBeTrue()
        ->and(($this->exists)())
        ->toBeTrue();
});
