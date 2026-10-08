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
use OpenDxp\Tool\Storage;

it('stores the data of an asset', function () {
    $data = file_get_contents(AssetImageFactory::fixture());

    $image = AssetImageFactory::createOne(['data' => $data]);

    expect(reloaded($image)->getData())->toBe($data);
});

it('stores the data an asset is given later', function () {
    $image = AssetImageFactory::createOne();
    $replacement = file_get_contents(fixture('image-large.jpg'));
    $image->setData($replacement);

    $image->save();

    expect(reloaded($image)->getData())->toBe($replacement);
});

it('renames the file when new data comes with another file extension', function () {
    $image = AssetImageFactory::createOne(['filename' => 'harbour.jpg']);
    $image->setData(file_get_contents(fixture('image.png')));
    $image->setFilename('harbour.png');
    $storage = Storage::get('asset');

    $image->save();

    expect($storage->fileExists($image->getRealFullPath()))
        ->toBeTrue()
        ->and($storage->fileExists(sprintf('%s/harbour.jpg', $image->getRealPath())))
        ->toBeFalse();
});
