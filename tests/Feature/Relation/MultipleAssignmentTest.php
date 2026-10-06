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
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Tests\Factory\MultipleAssignmentsFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

/**
 * @param class-string<ElementMetadata|ObjectMetadata> $metadata
 *
 * @return list<ElementMetadata|ObjectMetadata>
 */
function eachTargetTwice(string $metadata, string $field): array
{
    $relations = [];

    foreach (RelationTestFactory::createMany(3) as $position => $target) {
        foreach (['first', 'second'] as $which) {
            $relation = new $metadata($field, ['meta'], $target);
            $relation->setMeta(sprintf('%s note of %d', $which, $position));
            $relations[] = $relation;
        }
    }

    return $relations;
}

dataset('fields that allow a target twice', [
    'an element relation' => [ElementMetadata::class, 'multipleManyToMany'],
    'an object relation' => [ObjectMetadata::class, 'multipleManyToManyObject'],
]);

it('refuses the same target twice on a field that allows it once', function (string $metadata, string $field) {
    MultipleAssignmentsFactory::createOne([$field => eachTargetTwice($metadata, $field)]);
})->with([
    'an element relation' => [ElementMetadata::class, 'onlyOneManyToMany'],
    'an object relation' => [ObjectMetadata::class, 'onlyOneManyToManyObject'],
])->throws(ValidationException::class, 'Passing relations multiple times not allowed anymore');

it('keeps the same target twice on a field that allows it', function (string $metadata, string $field) {
    $relations = eachTargetTwice($metadata, $field);
    $object = MultipleAssignmentsFactory::createOne([$field => $relations]);

    $loaded = reloaded($object);

    expect(notesOf($loaded->get($field)))->toBe(notesOf($relations));
})->with('fields that allow a target twice');

it('keeps the same target twice in a serialized object', function (string $metadata, string $field) {
    $relations = eachTargetTwice($metadata, $field);
    $object = MultipleAssignmentsFactory::createOne([$field => $relations]);

    $copy = unserialize(serialize(reloaded($object)));

    expect(notesOf($copy->get($field)))->toBe(notesOf($relations));
})->with('fields that allow a target twice');
