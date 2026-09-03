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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\ApplicationLoggerBundle\Maintenance;

use Carbon\Carbon;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemOperator;
use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use OpenDxp\Bundle\ApplicationLoggerBundle\Schema\ApplicationLogSchema;
use OpenDxp\Config;
use OpenDxp\DateFormat;
use OpenDxp\Maintenance\TaskInterface;
use OpenDxp\Tool\Storage;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class LogArchiveTask implements TaskInterface
{
    private const int BATCH_SIZE = 1000;

    public function __construct(
        private readonly Connection $db,
        private Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $storage = Storage::get('application_log');

        $date = new DateTime('now');
        $archiveTable = sprintf('%s_%s', ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX, $date->format(DateFormat::ARCHIVE_DATE));

        if (!empty($this->config['applicationlog']['archive_alternative_database'])) {
            $archiveTable = sprintf(
                '%s.%s',
                $this->db->quoteIdentifier($this->config['applicationlog']['archive_alternative_database']),
                $archiveTable
            );
        }

        $archiveThreshold = (int) ($this->config['applicationlog']['archive_treshold'] ?? 30);
        $sourceTable = ApplicationLoggerDb::TABLE_NAME;
        $cutoff = (new DateTimeImmutable())->modify(sprintf('-%d days', $archiveThreshold))->format(DateFormat::DATETIME);
        $whereParams = [$cutoff];

        $count = $this->db->fetchOne(
            sprintf('SELECT COUNT(*) FROM %s WHERE `timestamp` < ?', $sourceTable),
            $whereParams
        );

        if ($count > 0) {
            $this->db->executeStatement(ApplicationLogSchema::createArchiveTable($archiveTable));

            $this->logger->debug(sprintf(
                'Deleting referenced FileObjects of application_logs which are older than %d days',
                $archiveThreshold
            ));

            do {
                $rows = $this->db->fetchAllAssociative(
                    sprintf(
                        'SELECT id, fileobject FROM %s WHERE `timestamp` < ? ORDER BY id LIMIT %d',
                        $sourceTable,
                        self::BATCH_SIZE
                    ),
                    $whereParams
                );

                $this->archiveBatch($archiveTable, $sourceTable, $rows, $storage);
            } while (count($rows) === self::BATCH_SIZE);
        }

        $archiveTables = $this->db->fetchFirstColumn(
            'SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = ?
                AND table_name LIKE ?',
            [
                $this->config['applicationlog']['archive_alternative_database'] ?: $this->db->getDatabase(),
                ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_%',
            ]
        );

        foreach ($archiveTables as $archiveTableName) {
            if (preg_match('/^' . ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_(\d{4})_(\d{2})$/', $archiveTableName, $matches)) {
                $deleteArchiveLogDate = Carbon::createFromFormat(DateFormat::FOLDER_DATE, $matches[1] . '/' . $matches[2]);
                if ($deleteArchiveLogDate->add(new DateInterval('P' . ($this->config['applicationlog']['delete_archive_threshold'] ?? 6) . 'M')) < new DateTimeImmutable()) {
                    $this->db->executeStatement(sprintf(
                        'DROP TABLE IF EXISTS %s.%s',
                        $this->db->quoteIdentifier($this->config['applicationlog']['archive_alternative_database'] ?: $this->db->getDatabase()),
                        $this->db->quoteIdentifier($archiveTableName)
                    ));

                    $folderName = $deleteArchiveLogDate->format(DateFormat::FOLDER_DATE);
                    if ($storage->directoryExists($folderName)) {
                        $storage->deleteDirectory($folderName);
                    }
                }
            }
        }
    }

    private function archiveBatch(
        string $archiveTable,
        string $sourceTable,
        array $rows,
        FilesystemOperator $storage
    ): void {
        if ($rows === []) {
            return;
        }

        $ids = array_map(intval(...), array_column($rows, 'id'));

        $this->db->executeStatement(
            sprintf(
                'INSERT IGNORE INTO %s SELECT * FROM %s WHERE id IN (?)',
                $archiveTable,
                $sourceTable
            ),
            [$ids],
            [ArrayParameterType::INTEGER]
        );

        $this->db->executeStatement(
            sprintf('DELETE FROM %s WHERE id IN (?)', $sourceTable),
            [$ids],
            [ArrayParameterType::INTEGER]
        );

        foreach ($rows as $row) {
            $filePath = $row['fileobject'];
            if ($filePath !== null && $storage->fileExists($filePath)) {
                $storage->delete($filePath);
            }
        }
    }
}
