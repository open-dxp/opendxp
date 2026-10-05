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

use OpenDxp\Db;
use OpenDxp\Model\Version;
use OpenDxp\Model\Version\Adapter\VersionStorageAdapterInterface;
use OpenDxp\TestFoundation\Container;

function useVersionStorage(VersionStorageAdapterInterface $adapter): void
{
    Container::get(VersionStorageAdapterInterface::class)->setStorageAdapter($adapter);
}

/**
 * @return array<string, mixed>
 */
function storedVersion(int $elementId, string $type, int $versionCount): array
{
    return Db::get()->fetchAssociative(
        'SELECT v.id, v.binaryFileId, v.binaryFileHash, v.storageType, d.metaData, d.binaryData
         FROM versions v
         LEFT JOIN versionsData d ON v.id = d.id AND v.cid = d.cid AND v.ctype = d.ctype
         WHERE v.cid = ? AND v.ctype = ? AND v.versionCount = ?',
        [$elementId, $type, $versionCount],
    );
}

function newestVersionOfObject(int $objectId): Version
{
    $listing = new Version\Listing();
    $listing->setCondition("ctype = 'object' AND cid = ?", [$objectId]);
    $listing->setOrderKey('id');
    $listing->setOrder('DESC');
    $listing->setLimit(1);

    return $listing->load()[0];
}

function countedVersionsOfObject(int $objectId): int
{
    return (int) Db::get()->fetchOne(
        "SELECT COUNT(*) FROM versions WHERE cid = ? AND ctype = 'object'",
        [$objectId],
    );
}
