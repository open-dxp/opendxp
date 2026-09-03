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

namespace OpenDxp\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use OpenDxp\Config;

final class Version20260903064009 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $config = Config::getSystemConfiguration('applicationlog') ?? [];
        $database = ($config['archive_alternative_database'] ?? null) ?: $this->connection->getDatabase();

        $tables = $this->connection->fetchFirstColumn(
            'SELECT table_name
                FROM information_schema.columns
                WHERE table_schema = ?
                  AND table_name LIKE ?
                  AND column_name = ?
                  AND data_type = ?',
            [$database, ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_%', 'message', 'varchar']
        );

        foreach ($tables as $table) {
            $this->addSql(sprintf(
                'ALTER TABLE %s.%s
                    MODIFY `id` bigint(20) unsigned NOT NULL,
                    MODIFY `message` text NULL,
                    MODIFY `relatedobject` int(11) unsigned DEFAULT NULL',
                $this->connection->quoteIdentifier($database),
                $this->connection->quoteIdentifier($table)
            ));
        }
    }

    public function down(Schema $schema): void
    {
        // do nothing
    }
}
