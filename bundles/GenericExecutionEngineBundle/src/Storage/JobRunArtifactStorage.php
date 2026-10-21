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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Storage;

use League\Flysystem\FilesystemOperator;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunArtifact;
use OpenDxp\Tool\Storage;

/**
 * Uses the temporary storage. Core keeps it for files of batch processes.
 *
 * @internal
 */
final readonly class JobRunArtifactStorage
{
    private const string DIRECTORY = 'job-run';

    private FilesystemOperator $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage->getStorage('temp');
    }

    public function save(int $jobRunId, JobRunArtifact $artifact): void
    {
        $this->storage->writeStream($this->getPath($jobRunId, $artifact->filename), $artifact->stream);
    }

    public function has(int $jobRunId, string $filename): bool
    {
        return $this->storage->fileExists($this->getPath($jobRunId, $filename));
    }

    /**
     * @return resource
     */
    public function getStream(int $jobRunId, string $filename): mixed
    {
        return $this->storage->readStream($this->getPath($jobRunId, $filename));
    }

    public function getMimeType(int $jobRunId, string $filename): string
    {
        return $this->storage->mimeType($this->getPath($jobRunId, $filename));
    }

    public function delete(int $jobRunId): void
    {
        $this->storage->deleteDirectory($this->getDirectory($jobRunId));
    }

    public function deleteAll(): void
    {
        $this->storage->deleteDirectory(self::DIRECTORY);
    }

    /**
     * @return list<int>
     */
    public function getJobRunIds(): array
    {
        $jobRunIds = [];
        foreach ($this->storage->listContents(self::DIRECTORY) as $item) {
            if ($item->isDir()) {
                $jobRunIds[] = (int) basename($item->path());
            }
        }

        return $jobRunIds;
    }

    private function getDirectory(int $jobRunId): string
    {
        return sprintf('%s/%d', self::DIRECTORY, $jobRunId);
    }

    private function getPath(int $jobRunId, string $filename): string
    {
        return sprintf('%s/%s', $this->getDirectory($jobRunId), $filename);
    }
}
