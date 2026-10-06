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

namespace OpenDxp\Tests\Feature\Element;

use OpenDxp\Model\Element\Service;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('returns a copy without an id', function (string $element, string $factory) {
    $original = $factory::createOne();

    $copy = Service::cloneMe($original);

    expect($copy->getId())->toBeNull();
})->with('elements');

it('returns a copy of an object without a parent', function () {
    $original = UnittestFactory::createOne();

    $copy = Service::cloneMe($original);

    expect($copy)
        ->getParentId()
        ->toBeNull()
        ->getParent()
        ->toBeNull();
});

it('carries the properties over to a copy', function (string $element, string $factory) {
    $original = $factory::new()
        ->withProperty('colour', 'input', 'blue')
        ->create();

    $copy = Service::cloneMe($original);

    expect($copy->getProperty('colour'))->toBe('blue');
})->with('elements');

it('names a copy after its original', function (string $factory, string $key, string $copyName) {
    $original = $factory::createOne(['key' => $key]);

    $name = Service::getSafeCopyName(
        $key,
        $original->getParent(),
    );

    expect($name)->toBe($copyName);
})->with([
    'an asset' => [AssetImageFactory::class, 'photo.jpg', 'photo_copy.jpg'],
    'a document' => [DocumentPageFactory::class, 'about', 'about_copy'],
    'an object' => [UnittestFactory::class, 'shoe', 'shoe_copy'],
]);
