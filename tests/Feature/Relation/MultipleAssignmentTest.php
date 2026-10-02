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

use Exception;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Model\DataObject\MultipleAssignments;
use OpenDxp\Tests\Factory\MultipleAssignmentsFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

function assignEachTwice(string $metadata, string $field, array $targets): array
{
    $assigned = [];

    foreach ($targets as $position => $target) {
        foreach (['first', 'second'] as $which) {
            $entry = new $metadata($field, ['meta'], $target);
            $entry->setMeta(sprintf('%s note of %d', $which, $position));
            $assigned[] = $entry;
        }
    }

    return $assigned;
}

function notesOf(array $assigned): array
{
    return array_map(static fn (object $entry) => $entry->getMeta(), $assigned);
}

beforeEach(fn () => $this->targets = RelationTestFactory::createMany(3));

it('refuses the same target twice on a field that allows one assignment', function (string $metadata, string $field) {

    $object = MultipleAssignmentsFactory::new()->unsaved()->create();
    $object->{'set' . ucfirst($field)}(assignEachTwice($metadata, $field, $this->targets));

    $object->save();
})->with([
    'an element relation' => [ElementMetadata::class, 'onlyOneManyToMany'],
    'an object relation' => [ObjectMetadata::class, 'onlyOneManyToManyObject'],
])->throws(Exception::class);

it('keeps the same target twice on a field that allows it', function (string $metadata, string $field) {

    $assigned = assignEachTwice($metadata, $field, $this->targets);
    $expected = notesOf($assigned);

    $object = MultipleAssignmentsFactory::createOne([$field => $assigned]);
    $getter = 'get' . ucfirst($field);

    expect(notesOf($object->{$getter}()))->toBe($expected);

    $reloaded = MultipleAssignments::getById($object->getId(), ['force' => true]);

    expect(notesOf($reloaded->{$getter}()))
        ->toBe($expected)
        ->and(notesOf(unserialize(serialize($reloaded))->{$getter}()))
        ->toBe($expected);
})->with([
    'an element relation' => [ElementMetadata::class, 'multipleManyToMany'],
    'an object relation' => [ObjectMetadata::class, 'multipleManyToManyObject'],
]);
