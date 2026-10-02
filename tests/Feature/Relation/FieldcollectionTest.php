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
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Data\Unittestfieldcollection;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * Builds a collection of one item per value, each handed to the item through the given setter.
 */
function collectionOf(string $setter, array $values): Fieldcollection
{
    $items = new Fieldcollection();

    foreach ($values as $value) {
        $item = new Unittestfieldcollection();
        $item->{$setter}($value);
        $items->add($item);
    }

    return $items;
}

function reloaded(DataObject\Concrete $object): DataObject\Concrete
{
    return DataObject::getById($object->getId(), ['force' => true]);
}

function itemsOf(DataObject\Concrete $object): Fieldcollection
{
    return reloaded($object)->getFieldcollection();
}

beforeEach(fn () => $this->object = UnittestFactory::createOne());

it('keeps a relation of every item of a collection', function () {

    $targets = RelationTestFactory::createMany(3);

    $this->object->setFieldcollection(collectionOf('setFieldRelation', [[$targets[0]], [$targets[1]]]));
    $this->object->save();

    $again = reloaded($this->object);
    $again->getFieldcollection()->get(1)->setFieldRelation([$targets[2]]);
    $again->save();

    $after = itemsOf($this->object);

    expect($after->get(0)->getFieldRelation()[0]->getId())
        ->toBe($targets[0]->getId())
        ->and($after->get(1)->getFieldRelation()[0]->getId())
        ->toBe($targets[2]->getId());
});

it('holds no relation in an item that was emptied', function () {

    $targets = RelationTestFactory::createMany(2);

    $this->object->setFieldcollection(collectionOf('setFieldRelation', [[$targets[0]], [$targets[1]]]));
    $this->object->save();

    $again = reloaded($this->object);
    $again->getFieldcollection()->get(1)->setFieldRelation(null);
    $again->save();

    expect(itemsOf($this->object)->get(1)->getFieldRelation())->toBe([]);
});

it('drops the metadata of a target that was deleted', function () {

    $kept = AssetImageFactory::createOne();
    $gone = AssetImageFactory::createOne();

    $this->object->setFieldcollection(collectionOf('setAdvancedFieldRelation', [
        [new ElementMetadata('metadataUpper', [], $kept)],
        [new ElementMetadata('metadataUpper', [], $gone)],
    ]));
    $this->object->save();

    $gone->delete();

    // The relation is only dropped once the object is written again.
    reloaded($this->object)->save();

    $after = itemsOf($this->object);

    expect($after->get(0)->getAdvancedFieldRelation()[0]->getElementId())
        ->toBe($kept->getId())
        ->and($after->get(1)->getAdvancedFieldRelation())
        ->toBe([]);
});

it('keeps a localized relation of every item of a collection', function () {

    $targets = RelationTestFactory::createMany(3);
    $items = new Fieldcollection();

    foreach ([$targets[0], $targets[1]] as $target) {
        $item = new Unittestfieldcollection();
        $item->setLinput('textEN', 'en');
        $item->setLRelation($target, 'en');
        $items->add($item);
    }

    $this->object->setFieldcollection($items);
    $this->object->save();

    $again = reloaded($this->object);
    $again->getFieldcollection()->get(1)->setLRelation($targets[2], 'en');
    $again->save();

    $after = itemsOf($this->object);

    expect($after->get(0)->getLRelation('en')->getId())
        ->toBe($targets[0]->getId())
        ->and($after->get(1)->getLRelation('en')->getId())
        ->toBe($targets[2]->getId());
});

it('holds no localized relation in an item that was emptied', function () {

    $targets = RelationTestFactory::createMany(2);
    $items = new Fieldcollection();

    foreach ($targets as $target) {
        $item = new Unittestfieldcollection();
        $item->setLRelation($target, 'en');
        $items->add($item);
    }

    $this->object->setFieldcollection($items);
    $this->object->save();

    $again = reloaded($this->object);
    $again->getFieldcollection()->get(1)->setLRelation(null, 'en');
    $again->save();

    expect(itemsOf($this->object)->get(1)->getLRelation('en'))->toBeNull();
});

it('hands back nothing for a localized text that carries no content', function () {

    $items = new Fieldcollection();
    $items->add((new Unittestfieldcollection())->setLinput('', 'en'));
    $items->add(new Unittestfieldcollection());
    $items->add((new Unittestfieldcollection())->setLinput(null, 'en'));

    $this->object->setFieldcollection($items);
    $this->object->save();

    $after = itemsOf($this->object);

    expect($after->get(0)->getLinput('en'))
        ->toBeNull()
        ->and($after->get(1)->getLinput('en'))
        ->toBeNull()
        ->and($after->get(2)->getLinput('en'))
        ->toBeNull();
});
