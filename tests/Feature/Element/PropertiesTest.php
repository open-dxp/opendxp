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

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Tests\Factory\InheritanceFactory;

dataset('property holders', [
    'an asset folder' => [AssetFolderFactory::class, Asset::class],
    'a document folder' => [DocumentFolderFactory::class, Document::class],
    'an object' => [InheritanceFactory::class, AbstractObject::class],
]);

function reloaded(string $element, int $id): object
{
    return $element::getById($id, ['force' => true]);
}

it('hands back every property it was given', function (string $factory, string $element) {

    $holder = $factory::createOne();
    $holder->setProperty('textproperty1', 'input', 'first');
    $holder->setProperty('textproperty2', 'input', 'second');
    $holder->save();

    $loaded = reloaded($element, $holder->getId());

    expect($loaded->hasProperty('textproperty1'))
        ->toBeTrue()
        ->and($loaded->getProperty('textproperty1'))
        ->toBe('first')
        ->and($loaded->getProperty('textproperty2'))
        ->toBe('second');
})->with('property holders');

it('replaces a property that is set a second time', function (string $factory, string $element) {

    $holder = $factory::createOne();
    $holder->setProperty('textproperty1', 'input', 'first');
    $holder->save();

    $holder->setProperty('textproperty1', 'input', 'second');
    $holder->save();

    expect(reloaded($element, $holder->getId())->getProperty('textproperty1'))->toBe('second');
})->with('property holders');

it('loses a property that is set to nothing and keeps the others', function (string $factory, string $element) {

    $holder = $factory::createOne();
    $holder->setProperty('textproperty1', 'input', 'first');
    $holder->setProperty('textproperty2', 'input', 'second');
    $holder->save();

    $holder->setProperty('textproperty1', 'input', null);
    $holder->save();

    $loaded = reloaded($element, $holder->getId());

    expect($loaded->getProperty('textproperty1'))
        ->toBeNull()
        ->and($loaded->getProperty('textproperty2'))
        ->toBe('second');
})->with('property holders');

it('passes an inheritable property down to the element below', function (string $factory, string $element) {

    $parent = $factory::createOne();
    $child = $factory::createOne(['parentId' => $parent->getId()]);

    $parent->setProperty('textproperty3', 'input', 'inherited', false, true);
    $parent->save();

    expect(reloaded($element, $child->getId())->getProperty('textproperty3'))->toBe('inherited');
})->with('property holders');

it('hands back an element property as the element it points at', function (string $factory, string $element) {

    $image = AssetImageFactory::createOne();
    $holder = $factory::createOne();

    $holder->setProperty('assetProperty', 'asset', $image);
    $holder->save();

    $property = reloaded($element, $holder->getId())->getProperty('assetProperty');

    expect($property)
        ->toBeInstanceOf(Asset::class)
        ->and($property->getId())
        ->toBe($image->getId());
})->with('property holders');
