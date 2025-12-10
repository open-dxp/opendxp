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

namespace OpenDxp\Maintenance\Tasks;

use OpenDxp\Helper\FileSystemHelper;
use OpenDxp\Maintenance\TaskInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @internal
 */
class HousekeepingTask implements TaskInterface
{
    public function __construct(protected int $tmpFileTime, protected int $profilerTime)
    {
    }

    public function execute(): void
    {
        foreach (['dev'] as $environment) {
            $profilerDir = sprintf('%s/%s/profiler', OPENDXP_SYMFONY_CACHE_DIRECTORY, $environment);

            $this->deleteFilesInFolderOlderThanSeconds($profilerDir, $this->profilerTime);
        }
    }

    private function deleteFilesInFolderOlderThanSeconds(string $folder, int $seconds): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $directory = new RecursiveDirectoryIterator($folder);
        $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current, $key, $iterator) use ($seconds) {
            if (strpos($current->getFilename(), '-low-quality-preview.svg')) {
                // do not delete low quality image previews
                return false;
            }

            if ($current->isFile()) {
                if ($current->getATime() && $current->getATime() < (time() - $seconds)) {
                    return true;
                }
            } else {
                return true;
            }

            return false;
        });

        $iterator = new RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            /**
             * @var SplFileInfo $file
             */
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }

            if (FileSystemHelper::isDirEmpty($file->getPath())) {
                @rmdir($file->getPath());
            }
        }
    }
}
