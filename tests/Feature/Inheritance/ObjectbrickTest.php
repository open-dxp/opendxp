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
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Tests\Factory\InheritanceFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function brickOf(DataObject\Concrete $object): UnittestBrick
{
    return DataObject::getById($object->getId(), ['force' => true])->getMybricks()->getUnittestBrick();
}

beforeEach(function () {
    // Only the admin is handed a brick container for an object that holds no brick of its own.
    OpenDxp::setAdminMode();

    $this->parent = InheritanceFactory::createOne();

    $brick = new UnittestBrick($this->parent);
    $brick->setBrickinput('text of the parent');
    $this->parent->getMybricks()->setUnittestBrick($brick);
    $this->parent->save();

    $this->child = InheritanceFactory::createOne(['parentId' => $this->parent->getId()]);
    $this->child->getMybricks()->getUnittestBrick()->setBrickinput2('text of the child');
    $this->child->save();

    $this->grandchild = InheritanceFactory::createOne(['parentId' => $this->child->getId()]);
});

it('hands a brick value down the whole tree', function () {
    expect(brickOf($this->child)->getBrickinput())
        ->toBe('text of the parent')
        ->and(brickOf($this->grandchild)->getBrickinput())
        ->toBe('text of the parent');
});

it('keeps a brick value of its own next to an inherited one', function () {
    expect(brickOf($this->child)->getBrickinput2())->toBe('text of the child');
});

it('hands the new value down once the parent changed it', function () {

    $again = DataObject::getById($this->parent->getId(), ['force' => true]);
    $again->getMybricks()->getUnittestBrick()->setBrickinput('another text');
    $again->save();

    expect(brickOf($this->child)->getBrickinput())
        ->toBe('another text')
        ->and(brickOf($this->grandchild)->getBrickinput())
        ->toBe('another text');
});

it('keeps a relation of its own in a brick', function () {

    $related = UnittestFactory::createMany(3);

    $again = DataObject::getById($this->grandchild->getId(), ['force' => true]);
    $again->getMybricks()->getUnittestBrick()->setBrickLazyRelation($related);
    $again->save();

    expect(brickOf($this->grandchild)->getBrickLazyRelation())->toHaveCount(3);
});
