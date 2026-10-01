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


namespace OpenDxp\Tests\Feature\DataObject;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Folder;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('loads a folder as a folder', function () {

    $folder = DataObjectFolderFactory::createOne();
    RuntimeCache::clear();

    expect(DataObject::getById($folder->getId()))->toBeInstanceOf(Folder::class);
});

it('loads every column of the row it was stored in', function () {

    $object = UnittestFactory::createOne();
    RuntimeCache::clear();

    expect(DataObject::getById($object->getId()))
        ->toBeInstanceOf($object::class)
        ->and(DataObject::getById($object->getId())->getKey())
        ->toBe($object->getKey())
        ->and(DataObject::getById($object->getId())->getParentId())
        ->toBe($object->getParentId())
        ->and(DataObject::getById($object->getId())->getPublished())
        ->toBe($object->getPublished())
        ->and(DataObject::getById($object->getId())->getModificationDate())
        ->toBe($object->getModificationDate());
});

it('hands back the instance it already holds on a second load', function () {

    $object = UnittestFactory::createOne();
    RuntimeCache::clear();

    expect(DataObject::getById($object->getId()))->toBe(DataObject::getById($object->getId()));
});

it('builds a fresh instance when the load is forced', function () {

    $object = UnittestFactory::createOne();
    RuntimeCache::clear();

    $cached = DataObject::getById($object->getId());
    $forced = DataObject::getById($object->getId(), ['force' => true]);

    expect($forced)
        ->not->toBe($cached)
        ->and($forced->getId())
        ->toBe($object->getId());
});
