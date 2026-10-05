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


namespace OpenDxp\Tests\Feature\Version;

use OpenDxp\Db;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Model\Version;
use OpenDxp\Model\Version\Adapter\DatabaseVersionStorageAdapter;
use OpenDxp\Model\Version\Adapter\DelegateVersionStorageAdapter;
use OpenDxp\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(fn () => useVersionStorage(new FileSystemVersionStorageAdapter()));

it('writes a version on every save', function () {

    $object = UnittestFactory::createOne();

    expect(countedVersionsOfObject($object->getId()))->toBe(1);

    $object->save();

    expect(countedVersionsOfObject($object->getId()))->toBe(2);
});

it('writes no version while versioning is off', function () {

    $object = UnittestFactory::createOne();

    Version::disable();
    $object->save();

    expect(countedVersionsOfObject($object->getId()))->toBe(1);

    Version::enable();
    $object->save();

    expect(countedVersionsOfObject($object->getId()))->toBe(2);
});

it('keeps a related object out of the version of the one that points at it', function () {

    $target = UnittestFactory::createOne(['input' => str_repeat('x', 190)]);
    $source = UnittestFactory::createOne(['multihref' => [$target]]);

    $version = stream_get_contents(newestVersionOfObject($source->getId())->getFileStream());

    expect($version)
        ->not->toContain($target->getInput())
        ->and(Unittest::getById($source->getId(), ['force' => true])->getMultihref())
        ->toHaveCount(1);
});

it('writes an object version to the file system', function () {

    $stored = storedVersion(UnittestFactory::createOne()->getId(), 'object', 1);

    expect($stored['storageType'])
        ->toBe('fs')
        ->and($stored['binaryFileId'])
        ->toBeEmpty()
        ->and($stored['metaData'])
        ->toBeEmpty()
        ->and($stored['binaryData'])
        ->toBeEmpty();
});

it('writes an object version to the database', function () {

    useVersionStorage(new DatabaseVersionStorageAdapter(Db::get()));

    $stored = storedVersion(UnittestFactory::createOne()->getId(), 'object', 1);

    expect($stored['storageType'])
        ->toBe('db')
        ->and($stored['metaData'])
        ->not->toBeEmpty()
        ->and($stored['binaryFileId'])
        ->toBeEmpty()
        ->and($stored['binaryData'])
        ->toBeEmpty();
});

it('hands a version over the threshold to the file system', function () {

    useVersionStorage(new DelegateVersionStorageAdapter(
        10,
        new DatabaseVersionStorageAdapter(Db::get()),
        new FileSystemVersionStorageAdapter(),
    ));

    $object = UnittestFactory::createOne(['lastname' => str_repeat('y', 100)]);
    $stored = storedVersion($object->getId(), 'object', 1);

    expect($stored['storageType'])
        ->toBe('fs')
        ->and($stored['binaryFileId'])
        ->toBeEmpty()
        ->and($stored['metaData'])
        ->toBeEmpty()
        ->and($stored['binaryData'])
        ->toBeEmpty();
});
