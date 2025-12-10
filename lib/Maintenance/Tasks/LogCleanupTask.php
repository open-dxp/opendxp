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
use OpenDxp\Model\Tool\TmpStore;

/**
 * @internal
 */
class LogCleanupTask implements TaskInterface
{
    public function execute(): void
    {
        // we don't use the RotatingFileHandler of Monolog, since rotating asynchronously is recommended + compression
        $logFiles = glob(OPENDXP_LOG_DIRECTORY.'/*.log');

        foreach ($logFiles as $log) {
            $tmpStoreTimeId = 'log-'.basename($log);
            $lastTimeItem = TmpStore::get($tmpStoreTimeId);
            if ($lastTimeItem) {
                $lastTime = (int) $lastTimeItem->getData();
            } else {
                TmpStore::add($tmpStoreTimeId, time(), null, 86400 * 7);

                continue;
            }

            if (file_exists($log) && date('Y-m-d', $lastTime) !== date('Y-m-d')) {
                // archive log (will be cleaned up by maintenance)
                $archiveFilename = preg_replace('/\.log$/', '', $log).'-archive-'.date('Y-m-d', $lastTime).'.log';
                rename($log, $archiveFilename);

                $lastTimeItem->setData(time());
                $lastTimeItem->update(86400 * 7);
            }
        }

        // archive and cleanup logs
        $files = [];
        $logFiles = glob(OPENDXP_LOG_DIRECTORY.'/*-archive-*.log');
        if (is_array($logFiles)) {
            $files = [...$files, ...$logFiles];
        }
        $archivedLogFiles = glob(OPENDXP_LOG_DIRECTORY.'/*-archive-*.log.gz');
        if (is_array($archivedLogFiles)) {
            $files = [...$files, ...$archivedLogFiles];
        }

        foreach ($files as $file) {
            if (filemtime($file) < (time() - (86400 * 7))) { // we keep the logs for 7 days
                unlink($file);
            } elseif (!preg_match("/\.gz$/", $file)) {
                FileSystemHelper::gzCompressFile($file);
                unlink($file);
            }
        }
    }
}
