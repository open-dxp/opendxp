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

namespace OpenDxp\Tests\Feature\Inheritance;

use OpenDxp\Db;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\DataObject\TestProduct;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\TestCategoryFactory;
use OpenDxp\Test\Factory\TestProductFactory;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

/**
 * A listing reads a relation from the object view of the class. The view keeps it as comma separated ids.
 */
function relationColumnOf(Concrete $object): string|false
{
    return Db::get()->fetchOne(
        sprintf('SELECT accessories FROM object_%s WHERE oo_id = ?', $object->getClassId()),
        [$object->getId()],
    );
}

function countListedWithText(string $text): int
{
    $listing = new TestProduct\Listing();
    $listing->setCondition('sku = ?', [$text]);

    return count($listing->load());
}

beforeEach(function () {
    $this->parent = TestProductFactory::createOne(['sku' => 'text of the parent']);
    $this->child = TestProductFactory::new()
        ->withParent($this->parent)
        ->create();
});

it('keeps the value of the child instead of the value of its parent', function () {
    $child = TestProductFactory::new()
        ->withParent($this->parent)
        ->create(['sku' => 'text of the child']);

    $loaded = Elements::refresh($child);

    expect($loaded)->getSku()->toBe('text of the child');
});

it('gives the child the value of its parent while it holds none of its own', function () {
    $loaded = Elements::refresh($this->child);

    expect($loaded)->getSku()->toBe('text of the parent');
});

it('lists the parent and the child by the value the child inherits', function () {
    $count = countListedWithText('text of the parent');

    expect($count)->toBe(2);
});

it('lists only the parent by its value once the child holds its own', function () {
    $this->child->setSku('text of the child');
    $this->child->save();

    expect(countListedWithText('text of the parent'))->toBe(1);
});

it('gives the child no value while inherited values are turned off', function () {
    $value = Service::useInheritedValues(
        false,
        fn () => Elements::refresh($this->child)->getSku(),
    );

    expect($value)->toBeNull();
});

it('takes the value of the parent away from a child moved to the root', function () {
    $this->child->setParentId(1);
    $this->child->save();

    expect(Elements::refresh($this->child))->getSku()->toBeNull();
});

it('gives a moved object the value of its new parent', function () {
    $object = TestProductFactory::createOne();

    $object->setParentId($this->parent->getId());
    $object->save();

    expect(Elements::refresh($object))->getSku()->toBe('text of the parent');
});

it('gives the child the new value once the parent changed it', function () {
    $this->parent->setSku('another text');
    $this->parent->save();

    expect(Elements::refresh($this->child))->getSku()->toBe('another text');
});

it('gives the child a relation of the parent and writes its id into the object view', function () {
    $target = TestCategoryFactory::createOne();
    $this->parent->setAccessories([$target]);
    $this->parent->save();

    $relations = Elements::refresh($this->child)->getAccessories();

    expect($relations)
        ->toHaveCount(1)
        ->and($relations[0]->getId())
        ->toBe($target->getId())
        ->and(relationColumnOf($this->child))
        ->toBe(sprintf(',%d,', $target->getId()));
});

it('gives a relation of the parent to its grandchild through another kind of child', function (
    AbstractObject $between,
) {
    $target = TestCategoryFactory::createOne();
    $grandchild = TestProductFactory::new()
        ->withParent($between)
        ->create();
    $this->parent->setAccessories([$target]);
    $this->parent->save();

    $relations = Elements::refresh($grandchild)->getAccessories();

    expect($relations)
        ->toHaveCount(1)
        ->and($relations[0]->getId())
        ->toBe($target->getId())
        ->and(relationColumnOf($grandchild))
        ->toBe(sprintf(',%d,', $target->getId()));
})->with([
    'a folder' => fn () => DataObjectFolderFactory::new()
        ->withParent($this->parent)
        ->create(),
    'an object of another class' => fn () => TestFieldTypesFactory::new()
        ->withParent($this->parent)
        ->create(),
]);

it('gives the child the single relation of its parent', function () {
    $target = TestCategoryFactory::createOne();
    $parent = TestProductFactory::createOne(['category' => $target]);
    $child = TestProductFactory::new()
        ->withParent($parent)
        ->create();

    $relation = Elements::refresh($child)->getCategory();

    expect($relation)->getId()->toBe($target->getId());
});

it('gives the child no single relation while inherited values are turned off', function () {
    $parent = TestProductFactory::createOne(['category' => TestCategoryFactory::createOne()]);
    $child = TestProductFactory::new()
        ->withParent($parent)
        ->create();

    $relation = Service::useInheritedValues(
        false,
        fn () => Elements::refresh($child)->getCategory(),
    );

    expect($relation)->toBeNull();
});

it('keeps a single relation of the child that equals the relation of its parent', function () {
    $target = TestCategoryFactory::createOne();
    $parent = TestProductFactory::createOne(['category' => $target]);
    $child = TestProductFactory::new()
        ->withParent($parent)
        ->create(['category' => $target]);

    $relation = Service::useInheritedValues(
        false,
        fn () => Elements::refresh($child)->getCategory(),
    );

    expect($relation)->getId()->toBe($target->getId());
});
