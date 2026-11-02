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
use OpenDxp\Model\DataObject\Fieldcollection\Data\TestFeature;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\TestCategoryFactory;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

beforeEach(fn () => $this->targets = TestCategoryFactory::createMany(3));

it('changes the relation of one item and keeps the others', function () {
    $object = TestFieldTypesFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new TestFeature())->setDocuments([$this->targets[0]]),
            (new TestFeature())->setDocuments([$this->targets[1]]),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setDocuments([$this->targets[2]]);
    $object->save();

    $items = Elements::refresh($object)->getFieldcollection();
    expect($items->get(0)->getDocuments()[0]->getId())
        ->toBe($this->targets[0]->getId())
        ->and($items->get(1)->getDocuments()[0]->getId())
        ->toBe($this->targets[2]->getId());
});

it('keeps no relation in an item that was emptied', function () {
    $object = TestFieldTypesFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new TestFeature())->setDocuments([$this->targets[0]]),
            (new TestFeature())->setDocuments([$this->targets[1]]),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setDocuments([]);
    $object->save();

    $item = Elements::refresh($object)->getFieldcollection()->get(1);
    expect($item)->getDocuments()->toBe([]);
});

it('drops the relation with metadata to a target that was deleted', function () {
    $kept = AssetImageFactory::createOne();
    $deleted = AssetImageFactory::createOne();
    $object = TestFieldTypesFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new TestFeature())->setCertificates([
                new ElementMetadata('metadataUpper', [], $kept),
            ]),
            (new TestFeature())->setCertificates([
                new ElementMetadata('metadataUpper', [], $deleted),
            ]),
        )
        ->create();

    $deleted->delete();
    // The relation is only dropped once the object is written again.
    Elements::refresh($object)->save();

    $items = Elements::refresh($object)->getFieldcollection();
    expect($items->get(0)->getCertificates()[0]->getElementId())
        ->toBe($kept->getId())
        ->and($items->get(1)->getCertificates())
        ->toBe([]);
});

it('changes the localized relation of one item and keeps the others', function () {
    $object = TestFieldTypesFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new TestFeature())->setCategory($this->targets[0], 'en'),
            (new TestFeature())->setCategory($this->targets[1], 'en'),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setCategory($this->targets[2], 'en');
    $object->save();

    $items = Elements::refresh($object)->getFieldcollection();
    expect($items->get(0)->getCategory('en')->getId())
        ->toBe($this->targets[0]->getId())
        ->and($items->get(1)->getCategory('en')->getId())
        ->toBe($this->targets[2]->getId());
});

it('keeps no localized relation in an item that was emptied', function () {
    $object = TestFieldTypesFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            (new TestFeature())->setCategory($this->targets[0], 'en'),
            (new TestFeature())->setCategory($this->targets[1], 'en'),
        )
        ->create();

    $object->getFieldcollection()->get(1)->setCategory(null, 'en');
    $object->save();

    $item = Elements::refresh($object)->getFieldcollection()->get(1);
    expect($item)->getCategory('en')->toBeNull();
});
