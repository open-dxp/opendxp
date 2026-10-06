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

use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Tests\Factory\MultipleAssignmentsFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

/**
 * @return list<ElementMetadata>
 */
function notedRelations(int $count): array
{
    $relations = [];

    foreach (RelationTestFactory::createMany($count) as $position => $target) {
        $relation = new ElementMetadata('multipleManyToMany', ['meta'], $target);
        $relation->setMeta(sprintf('note %d', $position));
        $relations[] = $relation;
    }

    return $relations;
}

beforeEach(function () {
    $this->relations = notedRelations(4);
    $this->object = MultipleAssignmentsFactory::createOne(['multipleManyToMany' => $this->relations]);
    $this->field = $this->object->getClass()->getFieldDefinition('multipleManyToMany');
});

it('counts every relation of a field that held none as new', function () {
    $object = MultipleAssignmentsFactory::createOne();
    $object->setMultipleManyToMany($this->relations);

    $delta = $this->field->calculateDelta($object, ['context' => ['containerType' => 'object']]);

    expect($delta)
        ->newRelations
        ->toHaveCount(4)
        ->existingRelations
        ->toBeEmpty()
        ->updatedRelations
        ->toBeEmpty()
        ->removedRelations
        ->toBeEmpty();
});

it('counts a dropped relation as removed and the others as existing', function () {
    $this->object->setMultipleManyToMany(array_slice($this->relations, 0, 3));

    $delta = $this->field->calculateDelta($this->object, ['context' => ['containerType' => 'object']]);

    expect($delta)
        ->newRelations
        ->toBeEmpty()
        ->existingRelations
        ->toHaveCount(3)
        ->updatedRelations
        ->toBeEmpty()
        ->removedRelations
        ->toHaveCount(1);
});

it('counts every relation as removed once the field is emptied', function () {
    $this->object->setMultipleManyToMany([]);

    $delta = $this->field->calculateDelta($this->object, ['context' => ['containerType' => 'object']]);

    expect($delta)
        ->newRelations
        ->toBeEmpty()
        ->existingRelations
        ->toBeEmpty()
        ->updatedRelations
        ->toBeEmpty()
        ->removedRelations
        ->toHaveCount(4);
});

it('counts two swapped relations as updated and the others as existing', function () {
    $swapped = $this->relations;
    [$swapped[1], $swapped[2]] = [$swapped[2], $swapped[1]];
    $this->object->setMultipleManyToMany($swapped);

    $delta = $this->field->calculateDelta($this->object, ['context' => ['containerType' => 'object']]);

    expect($delta)
        ->newRelations
        ->toBeEmpty()
        ->existingRelations
        ->toHaveCount(2)
        ->updatedRelations
        ->toHaveCount(2)
        ->removedRelations
        ->toBeEmpty();
});

it('counts the relations behind a dropped one as updated', function () {
    $this->object->setMultipleManyToMany(array_slice($this->relations, 1));

    $delta = $this->field->calculateDelta($this->object, ['context' => ['containerType' => 'object']]);

    expect($delta)
        ->newRelations
        ->toBeEmpty()
        ->existingRelations
        ->toBeEmpty()
        ->updatedRelations
        ->toHaveCount(3)
        ->removedRelations
        ->toHaveCount(1);
});

it('writes swapped relations in their new order', function () {
    $swapped = $this->relations;
    [$swapped[1], $swapped[2]] = [$swapped[2], $swapped[1]];

    $this->object->setMultipleManyToMany($swapped);
    $this->object->save();

    expect(notesOf(reloaded($this->object)->getMultipleManyToMany()))->toBe([
        'note 0',
        'note 2',
        'note 1',
        'note 3',
    ]);
});
