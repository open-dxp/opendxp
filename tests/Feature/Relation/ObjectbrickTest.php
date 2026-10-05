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

namespace OpenDxp\Tests\Feature\Relation;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Tests\Factory\RelationTestFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function brickOf(DataObject\Concrete $object): UnittestBrick
{
    return DataObject::getById($object->getId(), ['force' => true])->getMybricks()->getUnittestBrick();
}

/**
 * A brick container only exists on an object that was saved, so the brick is attached afterwards.
 */
function objectWithBrickRelations(array $targets): DataObject\Concrete
{
    $object = UnittestFactory::createOne();

    $brick = new UnittestBrick($object);
    $brick->setBrickLazyRelation($targets);
    $object->getMybricks()->setUnittestBrick($brick);
    $object->save();

    return $object;
}

it('keeps the relations a brick was given', function () {

    $object = objectWithBrickRelations(RelationTestFactory::createMany(2));

    expect(brickOf($object)->getBrickLazyRelation())->toHaveCount(2);
});

it('keeps the relations a brick was left with', function () {

    $targets = RelationTestFactory::createMany(3);
    $object = objectWithBrickRelations([$targets[0], $targets[1]]);

    $reloaded = DataObject::getById($object->getId(), ['force' => true]);
    $reloaded->getMybricks()->getUnittestBrick()->setBrickLazyRelation([$targets[1]]);
    $reloaded->save();

    $relations = brickOf($object)->getBrickLazyRelation();

    expect($relations)
        ->toHaveCount(1)
        ->and($relations[0]->getId())
        ->toBe($targets[1]->getId());
});

it('holds no relation once the brick was emptied', function () {

    $object = objectWithBrickRelations(RelationTestFactory::createMany(2));

    $reloaded = DataObject::getById($object->getId(), ['force' => true]);
    $reloaded->getMybricks()->getUnittestBrick()->setBrickLazyRelation(null);
    $reloaded->save();

    expect(brickOf($object)->getBrickLazyRelation())->toBe([]);
});
