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

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\DataObject\Folder;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;

it('writes an image asset with a file behind it', function () {
    $image = AssetImageFactory::createOne();

    expect(reloaded($image))
        ->toBeInstanceOf(Image::class)
        ->getFileSize()
        ->toBeGreaterThan(0);
});

it('puts an asset into a folder', function () {
    $folder = AssetFolderFactory::createOne(['filename' => 'photos']);

    $image = AssetImageFactory::new()
        ->withParent($folder)
        ->create();

    expect($image->getFullPath())->toStartWith('/photos/');
});

it('writes an object folder', function () {
    $folder = DataObjectFolderFactory::createOne(['key' => 'products']);

    expect(reloaded($folder))
        ->toBeInstanceOf(Folder::class)
        ->getFullPath()
        ->toBe('/products');
});
