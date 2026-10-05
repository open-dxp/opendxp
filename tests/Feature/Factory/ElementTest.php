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

use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Model\Asset\Folder as AssetFolder;
use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\DataObject\Folder as ObjectFolder;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\StaticRouteFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Controller\DefaultController;

it('writes an image asset with a file behind it', function () {

    $image = AssetImageFactory::createOne();

    expect($image)
        ->toBeInstanceOf(Image::class)
        ->and($image->getId())
        ->toBeGreaterThan(0)
        ->and($image->getFileSize())
        ->toBeGreaterThan(0);
});

it('puts an asset into a folder', function () {

    $folder = AssetFolderFactory::createOne(['filename' => 'photos']);
    $image = AssetImageFactory::new()->withParent($folder)->create();

    expect($folder)
        ->toBeInstanceOf(AssetFolder::class)
        ->and($image->getParentId())
        ->toBe($folder->getId())
        ->and($image->getFullPath())->toStartWith('/photos/');
});

it('writes an object folder', function () {

    $folder = DataObjectFolderFactory::createOne(['key' => 'products']);

    expect($folder)
        ->toBeInstanceOf(ObjectFolder::class)
        ->and($folder->getFullPath())
        ->toBe('/products');
});

it('writes a user that can sign in', function () {

    $user = UserFactory::createOne();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->getId())
        ->toBeGreaterThan(0)
        ->and($user->isAdmin())
        ->toBeFalse()
        ->and(UserFactory::new()->admin()->create()->isAdmin())
        ->toBeTrue();
});

it('writes a static route', function () {

    $route = StaticRouteFactory::new()
        ->withPattern('/news/%text', '/news/%text')
        ->withController(DefaultController::class, 'defaultAction')
        ->create(['name' => 'news_detail']);

    expect($route)
        ->toBeInstanceOf(Staticroute::class)
        ->and($route->getId())
        ->not->toBeNull()
        ->and(Staticroute::getByName('news_detail')->getPattern())
        ->toBe('/news/%text')
        ->and($route->getController())
        ->toBe(DefaultController::class . '::defaultAction');
});
