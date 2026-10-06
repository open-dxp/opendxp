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
use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Tests\Factory\InheritanceFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * A listing reads a relation from the object view of the class. The view keeps it as comma separated ids.
 */
function relationColumnOf(Concrete $object): string|false
{
    return Db::get()->fetchOne(
        sprintf('SELECT relationobjects FROM object_%s WHERE oo_id = ?', $object->getClassId()),
        [$object->getId()],
    );
}

function countListedWithText(string $text): int
{
    $listing = new Inheritance\Listing();
    $listing->setCondition('normalinput = ?', [$text]);

    return count($listing->load());
}

beforeEach(function () {
    $this->parent = InheritanceFactory::createOne(['normalInput' => 'text of the parent']);
    $this->child = InheritanceFactory::new()
        ->withParent($this->parent)
        ->create();
});

it('keeps the value of the child instead of the value of its parent', function () {
    $child = InheritanceFactory::new()
        ->withParent($this->parent)
        ->create(['normalInput' => 'text of the child']);

    $loaded = reloaded($child);

    expect($loaded)->getNormalInput()->toBe('text of the child');
});

it('gives the child the value of its parent while it holds none of its own', function () {
    $loaded = reloaded($this->child);

    expect($loaded)->getNormalInput()->toBe('text of the parent');
});

it('lists the parent and the child by the value the child inherits', function () {
    $count = countListedWithText('text of the parent');

    expect($count)->toBe(2);
});

it('lists only the parent by its value once the child holds its own', function () {
    $this->child->setNormalInput('text of the child');
    $this->child->save();

    expect(countListedWithText('text of the parent'))->toBe(1);
});

it('gives the child no value while inherited values are turned off', function () {
    $value = Service::useInheritedValues(
        false,
        fn () => reloaded($this->child)->getNormalInput(),
    );

    expect($value)->toBeNull();
});

it('takes the value of the parent away from a child moved to the root', function () {
    $this->child->setParentId(1);
    $this->child->save();

    expect(reloaded($this->child))->getNormalInput()->toBeNull();
});

it('gives a moved object the value of its new parent', function () {
    $object = InheritanceFactory::createOne();

    $object->setParentId($this->parent->getId());
    $object->save();

    expect(reloaded($object))->getNormalInput()->toBe('text of the parent');
});

it('gives the child the new value once the parent changed it', function () {
    $this->parent->setNormalInput('another text');
    $this->parent->save();

    expect(reloaded($this->child))->getNormalInput()->toBe('another text');
});

it('gives the child a relation of the parent and writes its id into the object view', function () {
    $target = RelationTestFactory::createOne();
    $this->parent->setRelationobjects([$target]);
    $this->parent->save();

    $relations = reloaded($this->child)->getRelationobjects();

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
    $target = RelationTestFactory::createOne();
    $grandchild = InheritanceFactory::new()
        ->withParent($between)
        ->create();
    $this->parent->setRelationobjects([$target]);
    $this->parent->save();

    $relations = reloaded($grandchild)->getRelationobjects();

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
    'an object of another class' => fn () => UnittestFactory::new()
        ->withParent($this->parent)
        ->create(),
]);

it('gives the child the single relation of its parent', function () {
    $target = RelationTestFactory::createOne();
    $parent = InheritanceFactory::createOne(['relation' => $target]);
    $child = InheritanceFactory::new()
        ->withParent($parent)
        ->create();

    $relation = reloaded($child)->getRelation();

    expect($relation)->getId()->toBe($target->getId());
});

it('gives the child no single relation while inherited values are turned off', function () {
    $parent = InheritanceFactory::createOne(['relation' => RelationTestFactory::createOne()]);
    $child = InheritanceFactory::new()
        ->withParent($parent)
        ->create();

    $relation = Service::useInheritedValues(
        false,
        fn () => reloaded($child)->getRelation(),
    );

    expect($relation)->toBeNull();
});

it('keeps a single relation of the child that equals the relation of its parent', function () {
    $target = RelationTestFactory::createOne();
    $parent = InheritanceFactory::createOne(['relation' => $target]);
    $child = InheritanceFactory::new()
        ->withParent($parent)
        ->create(['relation' => $target]);

    $relation = Service::useInheritedValues(
        false,
        fn () => reloaded($child)->getRelation(),
    );

    expect($relation)->getId()->toBe($target->getId());
});
