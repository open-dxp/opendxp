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

use OpenDxp\Model\DataObject\Data\BlockElement;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\LazyLoading;
use OpenDxp\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use OpenDxp\Model\DataObject\Objectbrick\Data\LazyLoadingTest;
use OpenDxp\Tests\Datasets\LazyRelation;
use OpenDxp\Tests\Factory\LazyLoadingFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

const TARGETS = 5;

function loaded(int $id): LazyLoading
{
    return LazyLoading::getById($id, ['force' => true]);
}

/**
 * The child inherits the relation the parent was saved with.
 */
function parentAndChild(LazyLoading $object): array
{
    return ['the object itself' => $object->getId(), 'the one below it' => LazyLoadingFactory::createOne(['parentId' => $object->getId()])->getId()];
}

beforeEach(fn () => $this->targets = RelationTestFactory::createMany(TARGETS));

it('keeps a relation out of the serialized object and loads it when it is asked for', function (LazyRelation $relation) {

    $object = LazyLoadingFactory::new()->unsaved()->create();
    $object->{'set' . ucfirst($relation->onTheObject)}($relation->value($relation->onTheObject, $this->targets));
    $object->save();

    foreach (parentAndChild($object) as $which => $id) {
        $written = loaded($id);

        expect(serialize($written))
            ->not->toContain(...lazyLoadingInternals())
            ->not->toContain(relationContent());

        $held = $written->{'get' . ucfirst($relation->onTheObject)}();

        expect($relation->counted($held))->toBe($relation->expected);
        expect(serialize($written))->not->toContain(...lazyLoadingInternals());

        $cached = fromTheCache($written);

        expect($relation->counted($cached->{'get' . ucfirst($relation->onTheObject)}()))->toBe($relation->expected);
    }
})->with('lazy relations');

it('keeps a localized relation out of the serialized object and loads it when it is asked for', function (LazyRelation $relation) {

    $object = LazyLoadingFactory::new()->unsaved()->create();
    $object->{'set' . ucfirst($relation->localized)}($relation->value($relation->localized, $this->targets), 'en');
    $object->save();

    foreach (parentAndChild($object) as $id) {
        $written = loaded($id);

        expect(serialize($written))->not->toContain(relationContent());

        $held = $written->{'get' . ucfirst($relation->localized)}('en');

        expect($relation->counted($held))->toBe($relation->expected);

        $cached = fromTheCache($written);

        expect($relation->counted($cached->{'get' . ucfirst($relation->localized)}('en')))->toBe($relation->expected);
    }
})->with('lazy relations');

it('loads a relation of a block when the block is asked for', function (LazyRelation $relation, string $place, string $getter) {

    $object = LazyLoadingFactory::new()->unsaved()->create();
    $field = $relation->{$place};
    $object->{str_replace('get', 'set', $getter)}([[
        $field => new BlockElement($field, $relation->blockType, $relation->value($field, $this->targets)),
    ]]);
    $object->save();

    foreach (parentAndChild($object) as $id) {
        $written = loaded($id);
        $block = $written->{$getter}();

        expect($relation->counted($block[0][$field]->getData()))->toBe($relation->expected);

        $cached = fromTheCache($written);
        $block = $cached->{$getter}();

        expect($relation->counted($block[0][$field]->getData()))->toBe($relation->expected);
    }
})->with('lazy relations')->with([
    'a block' => ['inABlock', 'getTestBlock'],
    'a block that is loaded lazily' => ['inALazyBlock', 'getTestBlockLazyloaded'],
]);

it('loads a relation of a field collection when the item is asked for', function (LazyRelation $relation) {

    $object = LazyLoadingFactory::new()->unsaved()->create();
    $items = new Fieldcollection();
    $item = new Fieldcollection\Data\LazyLoadingTest();
    $item->{'set' . ucfirst($relation->onTheObject)}($relation->value($relation->onTheObject, $this->targets));
    $items->add($item);
    $object->setFieldcollection($items);
    $object->save();

    $written = loaded($object->getId());

    expect(serialize($written))->not->toContain(relationContent());

    $held = $written->getFieldcollection()->get(0)->{'get' . ucfirst($relation->onTheObject)}();

    expect($relation->counted($held))->toBe($relation->expected);

    $cached = fromTheCache($written);

    expect($relation->counted($cached->getFieldcollection()->get(0)->{'get' . ucfirst($relation->onTheObject)}()))
        ->toBe($relation->expected);
})->with('lazy relations');

it('loads a relation of a brick when the brick is asked for', function (LazyRelation $relation) {

    $object = LazyLoadingFactory::createOne();

    $brick = new LazyLoadingTest($object);
    $brick->{'set' . ucfirst($relation->onTheObject)}($relation->value($relation->onTheObject, $this->targets));
    $object->getBricks()->setLazyLoadingTest($brick);
    $object->save();

    foreach (parentAndChild($object) as $id) {
        $written = loaded($id);
        $held = $written->getBricks()->getLazyLoadingTest()->{'get' . ucfirst($relation->onTheObject)}();

        expect($relation->counted($held))->toBe($relation->expected);

        $cached = fromTheCache($written);

        expect($relation->counted($cached->getBricks()->getLazyLoadingTest()->{'get' . ucfirst($relation->onTheObject)}()))
            ->toBe($relation->expected);
    }
})->with('lazy relations');

it('keeps a localized relation of a field collection when only a plain field of the item is saved', function (LazyRelation $relation) {

    $object = LazyLoadingFactory::new()->unsaved()->create();
    $items = new Fieldcollection();
    $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
    $item->{'set' . ucfirst($relation->localized)}($relation->value($relation->localized, $this->targets), 'en');
    $items->add($item);
    $object->setFieldcollection($items);
    $object->save();

    $written = loaded($object->getId());
    $collection = $written->getFieldcollection();
    $first = $collection->get(0);
    $first->setNormalInput('a plain text');
    $collection->setItems([$first]);
    $written->save();

    $held = loaded($object->getId())
        ->getFieldcollection()
        ->get(0)
        ->{'get' . ucfirst($relation->localized)}('en');

    expect($relation->counted($held))->toBe($relation->expected);
})->with('lazy relations');

it('keeps a localized relation of a brick when only a plain field of the brick is saved', function (LazyRelation $relation) {

    $object = LazyLoadingFactory::createOne();

    $brick = new LazyLoadingLocalizedTest($object);
    $brick->{'set' . ucfirst($relation->localized)}($relation->value($relation->localized, $this->targets), 'en');
    $object->getBricks()->setLazyLoadingLocalizedTest($brick);
    $object->save();

    $written = loaded($object->getId());
    $written->getBricks()->getLazyLoadingLocalizedTest()->setLInput('a plain text');
    $written->save();

    $held = loaded($object->getId())
        ->getBricks()
        ->getLazyLoadingLocalizedTest()
        ->{'get' . ucfirst($relation->localized)}('en');

    expect($relation->counted($held))->toBe($relation->expected);
})->with('lazy relations');
