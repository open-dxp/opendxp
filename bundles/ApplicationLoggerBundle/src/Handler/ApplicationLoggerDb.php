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

namespace OpenDxp\Bundle\ApplicationLoggerBundle\Handler;

use DateTimeZone;
use Doctrine\DBAL\Connection;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use OpenDxp\Db;

class ApplicationLoggerDb extends AbstractProcessingHandler
{
    const TABLE_NAME = 'application_logs';

    const TABLE_ARCHIVE_PREFIX = 'application_logs_archive';

    public function __construct(private readonly Connection $db, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    public function write(LogRecord $record): void
    {
        $data = [
            'pid' => getmypid(),
            'priority' => $record->level->toPsrLogLevel(),
            'message' => $record->message,
            'timestamp' => $record->datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'component' => $record->context['component'] ?? $record->channel,
            'fileobject' => $record->context['fileObject'] ?? null,
            'relatedobject' => $record->context['relatedObject'] ?? null,
            'relatedobjecttype' => $record->context['relatedObjectType'] ?? null,
            'source' => $record->context['source'] ?? null,
        ];

        $this->db->insert(self::TABLE_NAME, $data);
    }

    /**
     * @return string[]
     */
    public static function getComponents(): array
    {
        $db = Db::get();

        return $db->fetchFirstColumn('SELECT component FROM ' . self::TABLE_NAME . ' WHERE NOT ISNULL(component) GROUP BY component;');
    }

    /**
     * @return string[]
     */
    public static function getPriorities(): array
    {
        $priorities = [];
        $priorityNames = [
            'debug' => 'DEBUG',
            'info' => 'INFO',
            'notice' => 'NOTICE',
            'warning' => 'WARN',
            'error' => 'ERR',
            'critical' => 'CRIT',
            'alert' => 'ALERT',
            'emergency' => 'EMERG',
        ];

        $db = Db::get();

        $priorityNumbers = $db->fetchFirstColumn('SELECT priority FROM ' . self::TABLE_NAME . ' WHERE NOT ISNULL(priority) GROUP BY priority;');
        foreach ($priorityNumbers as $priorityNumber) {
            $priorities[$priorityNumber] = $priorityNames[$priorityNumber];
        }

        return $priorities;
    }
}
