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

use OpenDxp\Model\Asset;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;

it('hands back the data it was saved with', function () {

    $image = AssetImageFactory::createOne();

    expect(Asset::getById($image->getId(), ['force' => true])->getData())
        ->toBe(file_get_contents(AssetImageFactory::fixture()));
});

it('is found under its new path once it was moved and renamed', function () {

    $image = AssetImageFactory::createOne();
    $folder = AssetFolderFactory::createOne();

    $image->setParentId($folder->getId());
    $image->setKey($image->getKey() . '_new');
    $image->save();

    $moved = Asset::getByPath(sprintf('%s/%s', $folder->getFullPath(), $image->getKey()));

    expect($moved)
        ->toBeInstanceOf(Asset::class)
        ->and($moved->getId())
        ->toBe($image->getId())
        ->and($moved->getData())
        ->toBe(file_get_contents(AssetImageFactory::fixture()))
        ->and($folder->hasChildren())
        ->toBeTrue();
});

it('hands back the data it was given later', function () {

    $image = AssetImageFactory::createOne();
    $replacement = file_get_contents(AssetImageFactory::fixture('image-large.jpg'));

    $image->setData($replacement);
    $image->save();

    expect(Asset::getById($image->getId(), ['force' => true])->getData())->toBe($replacement);
});

it('leaves its folder childless once it was deleted', function () {

    $folder = AssetFolderFactory::createOne();
    $image = AssetImageFactory::createOne(['parentId' => $folder->getId()]);

    expect($folder->hasChildren())->toBeTrue();

    $image->delete();

    expect($folder->hasChildren())->toBeFalse();
});
