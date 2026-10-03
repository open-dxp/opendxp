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


use OpenDxp\Model\DataObject\Classificationstore;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Tests\Datasets\LazyRelation;

dataset('lazy relations', [
    'a many to many relation' => [new LazyRelation(
        onTheObject: 'relations',
        localized: 'lrelations',
        inABlock: 'blockrelations',
        inALazyBlock: 'blockrelationsLazyLoaded',
        blockType: 'manyToManyRelation',
    )],
    'a many to many object relation' => [new LazyRelation(
        onTheObject: 'objects',
        localized: 'lobjects',
        inABlock: 'blockobjects',
        inALazyBlock: 'blockobjectsLazyLoaded',
        blockType: 'manyToManyObjectRelation',
    )],
    'an advanced many to many relation' => [new LazyRelation(
        onTheObject: 'advancedRelations',
        localized: 'ladvancedRelations',
        inABlock: 'blockadvancedRelations',
        inALazyBlock: 'blockadvancedRelationsLazyLoaded',
        blockType: 'advancedManyToManyRelation',
        metadata: ElementMetadata::class,
    )],
    'an advanced many to many object relation' => [new LazyRelation(
        onTheObject: 'advancedObjects',
        localized: 'ladvancedObjects',
        inABlock: 'blockadvancedObjects',
        inALazyBlock: 'blockadvancedObjectsLazyLoaded',
        blockType: 'advancedManyToManyObjectRelation',
        metadata: ObjectMetadata::class,
    )],
    'a many to one relation' => [new LazyRelation(
        onTheObject: 'relation',
        localized: 'lrelation',
        inABlock: 'blockrelation',
        inALazyBlock: 'blockrelationLazyLoaded',
        blockType: 'manyToOneRelation',
        expected: 1,
        single: true,
    )],
]);
