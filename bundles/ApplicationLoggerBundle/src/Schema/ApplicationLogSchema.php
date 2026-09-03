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

namespace OpenDxp\Bundle\ApplicationLoggerBundle\Schema;

/**
 * @internal
 */
final class ApplicationLogSchema
{
    private const string COLUMNS = <<<'SQL'
        `pid` int(11) NULL DEFAULT NULL,
        `timestamp` datetime NOT NULL,
        `message` text NULL,
        `priority` enum('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
        `fileobject` varchar(1024) DEFAULT NULL,
        `info` varchar(1024) DEFAULT NULL,
        `component` varchar(190) DEFAULT NULL,
        `source` varchar(190) DEFAULT NULL,
        `relatedobject` int(11) unsigned DEFAULT NULL,
        `relatedobjecttype` enum('object','document','asset') DEFAULT NULL,
        `maintenanceChecked` tinyint(1) DEFAULT NULL
        SQL;

    public static function createLogTable(string $table): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                %s,
                PRIMARY KEY (`id`),
                KEY `component` (`component`),
                KEY `timestamp` (`timestamp`),
                KEY `relatedobject` (`relatedobject`),
                KEY `priority` (`priority`),
                KEY `maintenanceChecked` (`maintenanceChecked`)
            ) DEFAULT CHARSET=utf8mb4',
            $table,
            self::COLUMNS
        );
    }

    public static function createArchiveTable(string $table): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                `id` bigint(20) unsigned NOT NULL,
                %s,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            $table,
            self::COLUMNS
        );
    }
}
