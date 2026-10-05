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

use OpenDxp;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Tests\Factory\InheritanceFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function loaded(DataObject\Concrete $object): Inheritance
{
    return Inheritance::getById($object->getId(), ['force' => true]);
}

/**
 * A listing reads a relation from this column of the object view, as comma separated ids.
 */
function relationColumn(DataObject\Concrete $object): string|false
{
    return Db::get()->fetchOne(
        sprintf('SELECT relationobjects FROM object_%s WHERE oo_id = ?', $object->getClassId()),
        [$object->getId()],
    );
}

beforeEach(function () {
    // Only the admin is handed an object that holds nothing of its own.
    OpenDxp::setAdminMode();

    $this->parent = InheritanceFactory::createOne(['normalInput' => 'text of the parent']);
    $this->child = InheritanceFactory::createOne([
        'parentId' => $this->parent->getId(),
        'normalInput' => 'text of the child',
    ]);
});

it('keeps the value an object was given instead of the one above it', function () {
    expect(loaded($this->child)->getNormalInput())->toBe('text of the child');
});

it('hands the value above down once the object holds none of its own', function () {

    $this->child->setNormalInput(null);
    $this->child->save();

    expect(loaded($this->child)->getNormalInput())->toBe('text of the parent');
});

it('finds both objects in a listing while the one below inherits', function () {

    $this->child->setNormalInput(null);
    $this->child->save();

    $listing = new Inheritance\Listing();
    $listing->setCondition('normalinput LIKE ?', ['%text of the parent%']);
    $listing->setLocale('de');

    expect($listing->load())->toHaveCount(2);
});

it('finds one object in a listing once the one below holds its own value', function () {

    $listing = new Inheritance\Listing();
    $listing->setCondition('normalinput LIKE ?', ['%text of the parent%']);
    $listing->setLocale('de');

    expect($listing->load())->toHaveCount(1);
});

it('hands nothing down while inherited values are turned off', function () {

    $this->child->setNormalInput(null);
    $this->child->save();

    Service::useInheritedValues(false, function () {
        expect(loaded($this->child)->getNormalInput())->toBeNull();
    });

    Service::useInheritedValues(true, function () {
        expect(loaded($this->child)->getNormalInput())->toBe('text of the parent');
    });
});

it('loses the inherited value when it is moved out and regains it when moved back', function () {

    $this->child->setNormalInput(null);
    $this->child->save();

    $this->child->setParentId(1);
    $this->child->save();

    expect($this->child->getNormalInput())->toBeNull();

    $this->child->setParentId($this->parent->getId());
    $this->child->save();

    expect($this->child->getNormalInput())->toBe('text of the parent');
});

it('hands the new value down once the object above changed it', function () {

    $this->child->setNormalInput(null);
    $this->child->save();

    $this->parent->setNormalInput('another text');
    $this->parent->save();

    expect(loaded($this->child)->getNormalInput())->toBe('another text');
});

it('hands a relation down and writes its ids into the object view', function () {

    $this->parent->setRelationobjects([$this->parent]);
    $this->parent->save();
    OpenDxp::collectGarbage();

    $inherited = loaded($this->child)->getRelationObjects();

    expect($inherited)
        ->toHaveCount(1)
        ->and($inherited[0]->getId())
        ->toBe($this->parent->getId())
        ->and(relationColumn($this->child))
        ->toBe(sprintf(',%d,', $this->parent->getId()));
});

it('hands a relation down across a folder', function () {

    $folder = DataObjectFolderFactory::createOne(['parentId' => $this->parent->getId()]);
    $below = InheritanceFactory::createOne(['parentId' => $folder->getId()]);

    $this->parent->setRelationobjects([$this->parent]);
    $this->parent->save();
    OpenDxp::collectGarbage();

    expect(loaded($below)->getRelationObjects())->toHaveCount(1);
});

it('hands a relation down across an object of another class', function () {

    $between = UnittestFactory::createOne(['parentId' => $this->parent->getId()]);
    $below = InheritanceFactory::createOne([
        'parentId' => $between->getId(),
        'normalInput' => 'text of its own',
    ]);

    $this->parent->setRelationobjects([$this->parent]);
    $this->parent->save();
    OpenDxp::collectGarbage();

    expect(loaded($below)->getNormalInput())
        ->toBe('text of its own')
        ->and(loaded($below)->getRelationObjects())
        ->toHaveCount(1)
        ->and(relationColumn($below))
        ->toBe(sprintf(',%d,', $this->parent->getId()));
});

it('hands a single relation down and lets the object below take it over', function () {

    $target = RelationTestFactory::createOne();
    $this->parent->setRelation($target);
    $this->parent->save();

    Service::useInheritedValues(true, function () use ($target) {
        expect(loaded($this->child)->getRelation()->getId())->toBe($target->getId());
    });

    Service::useInheritedValues(false, function () {
        expect(loaded($this->child)->getRelation())->toBeNull();
    });

    Service::useInheritedValues(true, function () use ($target) {
        $own = Concrete::getById($this->child->getId(), ['force' => true]);
        $own->setRelation($target);
        $own->save();
    });

    Service::useInheritedValues(false, function () use ($target) {
        expect(loaded($this->child)->getRelation()->getId())->toBe($target->getId());
    });
});
