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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\Version\Adapter;

use OpenDxp\Model\Version;

class ProxyVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected VersionStorageAdapterInterface $storageAdapter;

    public function __construct(FileSystemVersionStorageAdapter $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function getStorageType(?int $metaDataSize = null, ?int $binaryDataSize = null): string
    {
        return $this->storageAdapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->storageAdapter->save($version, $metaData, $binaryDataStream);
    }

    public function loadMetaData(Version $version): ?string
    {
        return $this->storageAdapter->loadMetaData($version);
    }

    public function loadBinaryData(Version $version): mixed
    {
        return $this->storageAdapter->loadBinaryData($version);
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->storageAdapter->getBinaryFileStream($version);
    }

    public function getFileStream(Version $version): mixed
    {
        return $this->storageAdapter->getFileStream($version);
    }

    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $this->storageAdapter->delete($version, $isBinaryHashInUse);
    }

    public function setStorageAdapter(VersionStorageAdapterInterface $adapter): void
    {
        $this->storageAdapter = $adapter;
    }
}
