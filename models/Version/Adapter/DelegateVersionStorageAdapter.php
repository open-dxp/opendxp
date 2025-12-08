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

use Exception;
use OpenDxp\Model\Version;

/**
 * @internal
 */
class DelegateVersionStorageAdapter implements VersionStorageAdapterInterface
{
    /**
     * @var array<string, VersionStorageAdapterInterface>
     */
    private array $adapters = [];

    public function __construct(protected int $byteThreshold,
        protected VersionStorageAdapterInterface $defaultAdapter,
        protected VersionStorageAdapterInterface $fallbackAdapter
    ) {
        $this->adapters[$defaultAdapter->getStorageType()] = $defaultAdapter;
        $this->adapters[$fallbackAdapter->getStorageType()] = $fallbackAdapter;
    }

    protected function getAdapter(?string $storageType = null): VersionStorageAdapterInterface
    {
        if (empty($storageType)) {
            return $this->defaultAdapter;
        }

        $adapter = $this->adapters[$storageType] ?? null;

        if (isset($adapter) === false) {
            throw new Exception('no adapter for storage type ' . $storageType . ' found.');
        }

        return $adapter;
    }

    public function loadMetaData(Version $version): ?string
    {
        return $this->getAdapter($version->getStorageType())->loadMetaData($version);
    }

    public function loadBinaryData(Version $version): mixed
    {
        return $this->getAdapter($version->getStorageType())->loadBinaryData($version);
    }

    public function getStorageType(?int $metaDataSize = null, ?int $binaryDataSize = null): string
    {
        if ($metaDataSize > $this->byteThreshold ||
            $binaryDataSize > $this->byteThreshold) {
            return $this->fallbackAdapter->getStorageType($metaDataSize, $binaryDataSize);
        }

        return $this->defaultAdapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->getAdapter($version->getStorageType())->save($version, $metaData, $binaryDataStream);
    }

    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $this->getAdapter($version->getStorageType())->delete($version, $isBinaryHashInUse);
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->getAdapter($version->getStorageType())->getBinaryFileStream($version);
    }

    public function getFileStream(Version $version): mixed
    {
        return $this->getAdapter($version->getStorageType())->getFileStream($version);
    }
}
