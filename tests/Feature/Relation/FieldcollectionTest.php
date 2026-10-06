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
use OpenDxp\Model\DataObject\Fieldcollection\Data\Unittestfieldcollection;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(fn () => $this->targets = RelationTestFactory::createMany(3));

it('changes the relation of one item and keeps the others', function () {
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new Unittestfieldcollection())->setFieldRelation([$this->targets[0]]),
            (new Unittestfieldcollection())->setFieldRelation([$this->targets[1]]),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setFieldRelation([$this->targets[2]]);
    $object->save();

    $items = reloaded($object)->getFieldcollection();
    expect($items->get(0)->getFieldRelation()[0]->getId())
        ->toBe($this->targets[0]->getId())
        ->and($items->get(1)->getFieldRelation()[0]->getId())
        ->toBe($this->targets[2]->getId());
});

it('keeps no relation in an item that was emptied', function () {
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new Unittestfieldcollection())->setFieldRelation([$this->targets[0]]),
            (new Unittestfieldcollection())->setFieldRelation([$this->targets[1]]),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setFieldRelation([]);
    $object->save();

    $item = reloaded($object)->getFieldcollection()->get(1);
    expect($item)->getFieldRelation()->toBe([]);
});

it('drops the relation with metadata to a target that was deleted', function () {
    $kept = AssetImageFactory::createOne();
    $deleted = AssetImageFactory::createOne();
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new Unittestfieldcollection())->setAdvancedFieldRelation([
                new ElementMetadata('metadataUpper', [], $kept),
            ]),
            (new Unittestfieldcollection())->setAdvancedFieldRelation([
                new ElementMetadata('metadataUpper', [], $deleted),
            ]),
        )
        ->create();

    $deleted->delete();
    // The relation is only dropped once the object is written again.
    reloaded($object)->save();

    $items = reloaded($object)->getFieldcollection();
    expect($items->get(0)->getAdvancedFieldRelation()[0]->getElementId())
        ->toBe($kept->getId())
        ->and($items->get(1)->getAdvancedFieldRelation())
        ->toBe([]);
});

it('changes the localized relation of one item and keeps the others', function () {
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new Unittestfieldcollection())->setLrelation($this->targets[0], 'en'),
            (new Unittestfieldcollection())->setLrelation($this->targets[1], 'en'),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setLrelation($this->targets[2], 'en');
    $object->save();

    $items = reloaded($object)->getFieldcollection();
    expect($items->get(0)->getLrelation('en')->getId())
        ->toBe($this->targets[0]->getId())
        ->and($items->get(1)->getLrelation('en')->getId())
        ->toBe($this->targets[2]->getId());
});

it('keeps no localized relation in an item that was emptied', function () {
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new Unittestfieldcollection())->setLrelation($this->targets[0], 'en'),
            (new Unittestfieldcollection())->setLrelation($this->targets[1], 'en'),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setLrelation(null, 'en');
    $object->save();

    $item = reloaded($object)->getFieldcollection()->get(1);
    expect($item)->getLrelation('en')->toBeNull();
});
