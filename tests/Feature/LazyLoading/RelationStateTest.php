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

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\BlockElement;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Model\DataObject\LazyLoading;
use OpenDxp\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use OpenDxp\Tests\Factory\LazyLoadingFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

it('hands back no relation to an unpublished object while those are hidden', function () {

    $hidden = RelationTestFactory::new()->unpublished()->create();
    $object = LazyLoadingFactory::createOne(['relations' => [$hidden]]);

    $written = LazyLoading::getById($object->getId(), ['force' => true]);

    expect($written->getRelations())->toHaveCount(0);

    Concrete::setHideUnpublished(false);

    expect($written->getRelations())->toHaveCount(1);
});

it('hands back no relation at all once the field was emptied', function () {

    $hidden = RelationTestFactory::new()->unpublished()->create();
    $object = LazyLoadingFactory::createOne(['relations' => [$hidden]]);

    $object->setRelations([]);
    $object->save();

    Concrete::setHideUnpublished(false);

    expect(LazyLoading::getById($object->getId(), ['force' => true])->getRelations())->toHaveCount(0);
});

it('writes the targets of a block into the serialized object, where a lazy field keeps them out', function () {

    $targets = RelationTestFactory::createMany(3);

    $inABlock = LazyLoadingFactory::createOne([
        'testBlock' => [['blockrelations' => new BlockElement('blockrelations', 'manyToManyRelation', $targets)]],
    ]);
    $onTheObject = LazyLoadingFactory::createOne(['relations' => $targets]);

    expect(serialize(LazyLoading::getById($inABlock->getId(), ['force' => true])))
        ->toContain(RelationTestFactory::CONTENT)
        ->and(serialize(LazyLoading::getById($onTheObject->getId(), ['force' => true])))
        ->not->toContain(RelationTestFactory::CONTENT);
});

it('calls an advanced relation clean until a metadata field of it changes', function () {

    $targets = RelationTestFactory::createMany(3);
    $assigned = array_map(
        static fn (Concrete $target) => new ObjectMetadata('advancedObjects', ['metadataUpper'], $target),
        $targets,
    );

    $object = LazyLoadingFactory::createOne(['advancedObjects' => $assigned]);

    expect($object->isFieldDirty('advancedObjects'))->toBeFalse();

    RuntimeCache::clear();
    $written = LazyLoading::getById($object->getId(), ['force' => true]);

    expect($written->isFieldDirty('advancedObjects'))->toBeFalse();

    $written->getAdvancedObjects()[0]->setMetadataUpper('another note');

    expect($written->isFieldDirty('advancedObjects'))->toBeTrue();
});

it('keeps a localized relation of a brick apart per language', function () {

    $targets = RelationTestFactory::createMany(5);
    $object = LazyLoadingFactory::createOne();

    $brick = new LazyLoadingLocalizedTest($object);
    $brick->getLocalizedfields()->setLocalizedValue('lrelations', $targets, 'en');
    $brick->getLocalizedfields()->setLocalizedValue('lrelations', $targets, 'de');
    $object->getBricks()->setLazyLoadingLocalizedTest($brick);
    $object->save();

    $written = LazyLoading::getById($object->getId(), ['force' => true]);
    $localized = $written->getBricks()->getLazyLoadingLocalizedTest();

    expect($localized->getLRelations('en'))
        ->toHaveCount(5)
        ->and($localized->getLRelations('de'))
        ->toHaveCount(5);

    array_pop($targets);
    $localized->getLocalizedfields()->setLocalizedValue('lrelations', $targets, 'de');
    $written->save();

    $again = LazyLoading::getById($object->getId(), ['force' => true])
        ->getBricks()
        ->getLazyLoadingLocalizedTest();

    expect($again->getLRelations('de'))
        ->toHaveCount(4)
        ->and($again->getLRelations('en'))
        ->toHaveCount(5);
});
