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

namespace OpenDxp\Tests\Feature\LazyLoading;

use Closure;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\BlockElement;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\LazyLoading;
use OpenDxp\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use OpenDxp\Model\DataObject\Objectbrick\Data\LazyLoadingTest;
use OpenDxp\Tests\Factory\LazyLoadingFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;
use OpenDxp\Tests\Value\LazyRelation;

const TARGET_COUNT = 5;

/**
 * A lazy loaded field keeps these internals, and none of them may end up in a serialized object.
 */
const LAZY_LOADING_INTERNALS = [
    'lazyLoadedFields',
    'lazyKeys',
    'loadedLazyKeys',
];

dataset('lazy relations', [
    'a many to many relation' => fn () => new LazyRelation(
        onTheObject: 'relations',
        localized: 'lrelations',
        inABlock: 'blockrelations',
        inALazyBlock: 'blockrelationsLazyLoaded',
        blockType: 'manyToManyRelation',
        expected: TARGET_COUNT,
        value: fn (string $field, array $targets) => $targets,
        count: fn (array $loaded) => count($loaded),
    ),
    'a many to many object relation' => fn () => new LazyRelation(
        onTheObject: 'objects',
        localized: 'lobjects',
        inABlock: 'blockobjects',
        inALazyBlock: 'blockobjectsLazyLoaded',
        blockType: 'manyToManyObjectRelation',
        expected: TARGET_COUNT,
        value: fn (string $field, array $targets) => $targets,
        count: fn (array $loaded) => count($loaded),
    ),
    'an advanced many to many relation' => fn () => new LazyRelation(
        onTheObject: 'advancedRelations',
        localized: 'ladvancedRelations',
        inABlock: 'blockadvancedRelations',
        inALazyBlock: 'blockadvancedRelationsLazyLoaded',
        blockType: 'advancedManyToManyRelation',
        expected: TARGET_COUNT,
        value: fn (string $field, array $targets) => array_map(
            fn (Concrete $target) => new ElementMetadata($field, [], $target),
            $targets,
        ),
        count: fn (array $loaded) => count($loaded),
    ),
    'an advanced many to many object relation' => fn () => new LazyRelation(
        onTheObject: 'advancedObjects',
        localized: 'ladvancedObjects',
        inABlock: 'blockadvancedObjects',
        inALazyBlock: 'blockadvancedObjectsLazyLoaded',
        blockType: 'advancedManyToManyObjectRelation',
        expected: TARGET_COUNT,
        value: fn (string $field, array $targets) => array_map(
            fn (Concrete $target) => new ObjectMetadata($field, [], $target),
            $targets,
        ),
        count: fn (array $loaded) => count($loaded),
    ),
    'a many to one relation' => fn () => new LazyRelation(
        onTheObject: 'relation',
        localized: 'lrelation',
        inABlock: 'blockrelation',
        inALazyBlock: 'blockrelationLazyLoaded',
        blockType: 'manyToOneRelation',
        expected: 1,
        value: fn (string $field, array $targets) => $targets[0],
        count: fn (?Concrete $loaded) => (int) ($loaded instanceof Concrete),
    ),
]);

/**
 * A child inherits the relations its parent was saved with, so both of them hold the relation.
 */
dataset('holders', [
    'the object' => fn (LazyLoading $object) => $object,
    'its child' => fn (LazyLoading $object) => LazyLoadingFactory::new()
        ->withParent($object)
        ->create(),
]);

dataset('blocks', [
    'a block' => [
        'testblock',
        fn (LazyRelation $relation) => $relation->inABlock,
    ],
    'a block that is loaded lazily' => [
        'testblockLazyloaded',
        fn (LazyRelation $relation) => $relation->inALazyBlock,
    ],
]);

beforeEach(function () {
    $this->targets = RelationTestFactory::new()
        ->marked()
        ->many(TARGET_COUNT)
        ->create();
});

it('keeps a relation out of the serialized object', function (LazyRelation $relation, Closure $holderOf) {
    $field = $relation->onTheObject;
    $object = LazyLoadingFactory::createOne([$field => $relation->value($field, $this->targets)]);
    $holder = reloaded($holderOf($object));

    $serialized = serialize($holder);

    expect($serialized)
        ->not->toContain(...LAZY_LOADING_INTERNALS)
        ->not->toContain(RelationTestFactory::MARKER);
})->with('lazy relations')->with('holders');

it('loads a relation when it is asked for', function (LazyRelation $relation, Closure $holderOf) {
    $field = $relation->onTheObject;
    $object = LazyLoadingFactory::createOne([$field => $relation->value($field, $this->targets)]);
    $holder = reloaded($holderOf($object));

    $loaded = $holder->get($field);

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders');

it('keeps the lazy loading internals out of an object serialized after loading', function (
    LazyRelation $relation,
    Closure $holderOf,
) {
    $field = $relation->onTheObject;
    $object = LazyLoadingFactory::createOne([$field => $relation->value($field, $this->targets)]);
    $holder = reloaded($holderOf($object));
    $holder->get($field);

    $serialized = serialize($holder);

    expect($serialized)->not->toContain(...LAZY_LOADING_INTERNALS);
})->with('lazy relations')->with('holders');

it('loads a relation of an object read from the cache', function (LazyRelation $relation, Closure $holderOf) {
    $field = $relation->onTheObject;
    $object = LazyLoadingFactory::createOne([$field => $relation->value($field, $this->targets)]);
    $cached = cachedCopyOf(reloaded($holderOf($object)));

    $loaded = $cached->get($field);

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders');

it('keeps a localized relation out of the serialized object', function (LazyRelation $relation, Closure $holderOf) {
    $field = $relation->localized;
    $object = LazyLoadingFactory::new()
        ->withLocalizedValues($field, ['en' => $relation->value($field, $this->targets)])
        ->create();
    $holder = reloaded($holderOf($object));

    $serialized = serialize($holder);

    expect($serialized)->not->toContain(RelationTestFactory::MARKER);
})->with('lazy relations')->with('holders');

it('loads a localized relation when it is asked for', function (LazyRelation $relation, Closure $holderOf) {
    $field = $relation->localized;
    $object = LazyLoadingFactory::new()
        ->withLocalizedValues($field, ['en' => $relation->value($field, $this->targets)])
        ->create();
    $holder = reloaded($holderOf($object));

    $loaded = $holder->get($field, 'en');

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders');

it('loads a localized relation of an object read from the cache', function (
    LazyRelation $relation,
    Closure $holderOf,
) {
    $field = $relation->localized;
    $object = LazyLoadingFactory::new()
        ->withLocalizedValues($field, ['en' => $relation->value($field, $this->targets)])
        ->create();
    $cached = cachedCopyOf(reloaded($holderOf($object)));

    $loaded = $cached->get($field, 'en');

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders');

it('loads a relation of a block when the block is asked for', function (
    LazyRelation $relation,
    Closure $holderOf,
    string $block,
    Closure $fieldOf,
) {
    $field = $fieldOf($relation);
    $element = new BlockElement($field, $relation->blockType, $relation->value($field, $this->targets));
    $object = LazyLoadingFactory::createOne([$block => [[$field => $element]]]);
    $holder = reloaded($holderOf($object));

    $loaded = $holder->get($block)[0][$field]->getData();

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders')->with('blocks');

it('loads a relation of a block of an object read from the cache', function (
    LazyRelation $relation,
    Closure $holderOf,
    string $block,
    Closure $fieldOf,
) {
    $field = $fieldOf($relation);
    $element = new BlockElement($field, $relation->blockType, $relation->value($field, $this->targets));
    $object = LazyLoadingFactory::createOne([$block => [[$field => $element]]]);
    $cached = cachedCopyOf(reloaded($holderOf($object)));

    $loaded = $cached->get($block)[0][$field]->getData();

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders')->with('blocks');

it('keeps a relation of a field collection out of the serialized object', function (LazyRelation $relation) {
    $item = new Fieldcollection\Data\LazyLoadingTest();
    $item->set($relation->onTheObject, $relation->value($relation->onTheObject, $this->targets));
    $object = LazyLoadingFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $item,
        )
        ->create();

    $serialized = serialize(reloaded($object));

    expect($serialized)->not->toContain(RelationTestFactory::MARKER);
})->with('lazy relations');

it('loads a relation of a field collection when the item is asked for', function (LazyRelation $relation) {
    $item = new Fieldcollection\Data\LazyLoadingTest();
    $item->set($relation->onTheObject, $relation->value($relation->onTheObject, $this->targets));
    $object = LazyLoadingFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $item,
        )
        ->create();
    $written = reloaded($object);

    $loaded = $written->getFieldcollection()->get(0)->get($relation->onTheObject);

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations');

it('loads a relation of a field collection of an object read from the cache', function (LazyRelation $relation) {
    $item = new Fieldcollection\Data\LazyLoadingTest();
    $item->set($relation->onTheObject, $relation->value($relation->onTheObject, $this->targets));
    $object = LazyLoadingFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $item,
        )
        ->create();
    $cached = cachedCopyOf(reloaded($object));

    $loaded = $cached->getFieldcollection()->get(0)->get($relation->onTheObject);

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations');

it('loads a relation of a brick when the brick is asked for', function (LazyRelation $relation, Closure $holderOf) {
    $field = $relation->onTheObject;
    $object = LazyLoadingFactory::new()
        ->withObjectbrick(
            'bricks',
            LazyLoadingTest::class,
            [$field => $relation->value($field, $this->targets)],
        )
        ->create();
    $holder = reloaded($holderOf($object));

    $loaded = $holder->getBricks()->getLazyLoadingTest()->get($field);

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders');

it('loads a relation of a brick of an object read from the cache', function (
    LazyRelation $relation,
    Closure $holderOf,
) {
    $field = $relation->onTheObject;
    $object = LazyLoadingFactory::new()
        ->withObjectbrick(
            'bricks',
            LazyLoadingTest::class,
            [$field => $relation->value($field, $this->targets)],
        )
        ->create();
    $cached = cachedCopyOf(reloaded($holderOf($object)));

    $loaded = $cached->getBricks()->getLazyLoadingTest()->get($field);

    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations')->with('holders');

it('writes the targets of a relation in an eagerly loaded block into the serialized object', function () {
    $element = new BlockElement('blockrelations', 'manyToManyRelation', $this->targets);
    $object = LazyLoadingFactory::createOne(['testblock' => [['blockrelations' => $element]]]);

    $serialized = serialize(reloaded($object));

    expect($serialized)->toContain(RelationTestFactory::MARKER);
});

it('changes a localized relation of a brick in one language only', function () {
    $object = LazyLoadingFactory::new()
        ->withLocalizedObjectbrick(
            'bricks',
            LazyLoadingLocalizedTest::class,
            [
                'en' => ['lrelations' => $this->targets],
                'de' => ['lrelations' => $this->targets],
            ],
        )
        ->create();
    $written = reloaded($object);

    $written->getBricks()->getLazyLoadingLocalizedTest()->setLrelations(array_slice($this->targets, 1), 'de');
    $written->save();

    $brick = reloaded($object)->getBricks()->getLazyLoadingLocalizedTest();
    expect($brick->getLrelations('de'))
        ->toHaveCount(TARGET_COUNT - 1)
        ->and($brick->getLrelations('en'))
        ->toHaveCount(TARGET_COUNT);
});

it('keeps a localized relation of a field collection when a plain field of the item is saved', function (
    LazyRelation $relation,
) {
    $field = $relation->localized;
    $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
    $item->set($field, $relation->value($field, $this->targets), 'en');
    $object = LazyLoadingFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $item,
        )
        ->create();
    $written = reloaded($object);

    $written->getFieldcollection()->get(0)->setNormalInput('a plain text');
    $written->save();

    $loaded = reloaded($object)->getFieldcollection()->get(0)->get($field, 'en');
    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations');

it('keeps a localized relation of a brick when another field of the brick is saved', function (
    LazyRelation $relation,
) {
    $field = $relation->localized;
    $object = LazyLoadingFactory::new()
        ->withLocalizedObjectbrick(
            'bricks',
            LazyLoadingLocalizedTest::class,
            ['en' => [$field => $relation->value($field, $this->targets)]],
        )
        ->create();
    $written = reloaded($object);

    $written->getBricks()->getLazyLoadingLocalizedTest()->setLinput('a plain text', 'en');
    $written->save();

    $loaded = reloaded($object)->getBricks()->getLazyLoadingLocalizedTest()->get($field, 'en');
    expect($relation->counted($loaded))->toBe($relation->expected);
})->with('lazy relations');
