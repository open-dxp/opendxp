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

use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Tests\Factory\MultipleAssignmentsFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

const FIELD = 'multipleManyToMany';

function changed(Data $field, Concrete $object): array
{
    $delta = $field->calculateDelta($object, ['context' => ['containerType' => 'object']]);

    return [
        'new' => count($delta['newRelations']),
        'existing' => count($delta['existingRelations']),
        'updated' => count($delta['updatedRelations']),
        'removed' => count($delta['removedRelations']),
    ];
}

function swapped(array $relations, int $one, int $other): array
{
    [$relations[$one], $relations[$other]] = [$relations[$other], $relations[$one]];

    return $relations;
}

function notes(array $relations): array
{
    return array_map(static fn (object $relation) => $relation->getMeta(), $relations);
}

it('tells what changed in a relation after every edit', function () {

    $object = MultipleAssignmentsFactory::createOne();
    $field = $object->getClass()->getFieldDefinition(FIELD);

    $assigned = [];

    foreach (RelationTestFactory::createMany(5) as $position => $target) {
        $entry = new ElementMetadata(FIELD, ['meta'], $target);
        $entry->setMeta('note ' . $position);
        $assigned[] = $entry;
    }

    $object->setMultipleManyToMany($assigned);

    expect(changed($field, $object))->toBe(['new' => 5, 'existing' => 0, 'updated' => 0, 'removed' => 0]);

    $object->save();

    array_pop($assigned);
    $object->setMultipleManyToMany($assigned);

    expect(changed($field, $object))->toBe(['new' => 0, 'existing' => 4, 'updated' => 0, 'removed' => 1]);

    $object->save();

    $object->setMultipleManyToMany([]);

    expect(changed($field, $object))->toBe(['new' => 0, 'existing' => 0, 'updated' => 0, 'removed' => 4]);

    $object->save();

    $object->setMultipleManyToMany($assigned);

    expect(changed($field, $object))->toBe(['new' => 4, 'existing' => 0, 'updated' => 0, 'removed' => 0]);

    $object->save();

    // A swap changes the position of two relations, not the relations themselves.
    $object->setMultipleManyToMany(swapped($assigned, 1, 2));

    expect(changed($field, $object))->toBe(['new' => 0, 'existing' => 2, 'updated' => 2, 'removed' => 0]);

    $object->save();

    expect(notes($object->getMultipleManyToMany()))->toBe(['note 0', 'note 2', 'note 1', 'note 3']);

    $left = swapped($object->getMultipleManyToMany(), 0, 2);
    array_shift($left);
    $object->setMultipleManyToMany($left);

    expect(changed($field, $object))->toBe(['new' => 0, 'existing' => 0, 'updated' => 3, 'removed' => 1]);

    $object->save();

    expect(notes($object->getMultipleManyToMany()))->toBe(['note 2', 'note 0', 'note 3']);
});
