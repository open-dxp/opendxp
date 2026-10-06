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
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Version;
use OpenDxp\Model\Version\Adapter\DatabaseVersionStorageAdapter;
use OpenDxp\Model\Version\Adapter\DelegateVersionStorageAdapter;
use OpenDxp\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use OpenDxp\Model\Version\Adapter\VersionStorageAdapterInterface;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;
use OpenDxp\Tests\Value\StoredVersion;
use RuntimeException;

function useVersionStorage(VersionStorageAdapterInterface $adapter): void
{
    Container::get(VersionStorageAdapterInterface::class)->setStorageAdapter($adapter);
}

function firstStoredVersionOf(Concrete $object): StoredVersion
{
    $row = Db::get()->fetchAssociative(
        'SELECT v.binaryFileId, v.storageType, d.metaData, d.binaryData
         FROM versions v
         LEFT JOIN versionsData d ON v.id = d.id AND v.cid = d.cid AND v.ctype = d.ctype
         WHERE v.cid = ? AND v.ctype = ? AND v.versionCount = ?',
        [
            $object->getId(),
            'object',
            1,
        ],
    );

    if ($row === false) {
        throw new RuntimeException(sprintf(
            'The object %d has no stored version.',
            $object->getId(),
        ));
    }

    return new StoredVersion(
        $row['storageType'],
        $row['binaryFileId'] === null ? null : (int) $row['binaryFileId'],
        $row['metaData'],
        $row['binaryData'],
    );
}

function newestVersionContentOf(Concrete $object): string
{
    $listing = new Version\Listing();
    $listing->setCondition(
        "ctype = 'object' AND cid = ?",
        [$object->getId()],
    );
    $listing->setOrderKey('id');
    $listing->setOrder('DESC');
    $listing->setLimit(1);
    $version = $listing->load()[0];

    return stream_get_contents($version->getFileStream());
}

function versionCountOf(Concrete $object): int
{
    return (int) Db::get()->fetchOne(
        "SELECT COUNT(*) FROM versions WHERE cid = ? AND ctype = 'object'",
        [$object->getId()],
    );
}

beforeEach(fn () => useVersionStorage(new FileSystemVersionStorageAdapter()));

it('writes a version on every save', function () {
    $object = UnittestFactory::createOne();

    $object->save();

    expect(versionCountOf($object))->toBe(2);
});

it('writes no version while versioning is off', function () {
    $object = UnittestFactory::createOne();
    Version::disable();

    $object->save();

    expect(versionCountOf($object))->toBe(1);
})->after(fn () => Version::enable());

it('keeps a related object out of the version of the object that points at it', function () {
    $target = UnittestFactory::createOne(['input' => 'the related object']);

    $source = UnittestFactory::createOne(['multihref' => [$target]]);

    expect(newestVersionContentOf($source))
        ->not->toContain('the related object')
        ->and(reloaded($source)->getMultihref())
        ->toHaveCount(1);
});

it('writes an object version to the file system', function () {
    $object = UnittestFactory::createOne();

    expect(firstStoredVersionOf($object))
        ->storageType
        ->toBe('fs')
        ->binaryFileId
        ->toBeNull()
        ->metaData
        ->toBeNull()
        ->binaryData
        ->toBeNull();
});

it('writes an object version to the database', function () {
    useVersionStorage(new DatabaseVersionStorageAdapter(Db::get()));

    $object = UnittestFactory::createOne();

    expect(firstStoredVersionOf($object))
        ->storageType
        ->toBe('db')
        ->metaData
        ->not->toBeEmpty()
        ->binaryFileId
        ->toBeNull()
        ->binaryData
        ->toBeNull();
});

it('writes a version over the threshold of a delegate to the file system', function () {
    useVersionStorage(new DelegateVersionStorageAdapter(
        10,
        new DatabaseVersionStorageAdapter(Db::get()),
        new FileSystemVersionStorageAdapter(),
    ));

    $object = UnittestFactory::createOne(['lastname' => str_repeat('y', 100)]);

    expect(firstStoredVersionOf($object))
        ->storageType
        ->toBe('fs')
        ->binaryFileId
        ->toBeNull()
        ->metaData
        ->toBeNull()
        ->binaryData
        ->toBeNull();
});
