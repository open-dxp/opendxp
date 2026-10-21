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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Migrations;

use DateTimeInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;

final class Version20261010120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            return;
        }

        $this->addSql(sprintf(
            'ALTER TABLE %s
                ADD jobName VARCHAR(255) DEFAULT NULL,
                ADD pendingOperations INT DEFAULT 0 NOT NULL,
                ADD dispatchedMessages INT UNSIGNED DEFAULT 0 NOT NULL,
                ADD succeededMessages INT UNSIGNED DEFAULT 0 NOT NULL,
                ADD failedMessages INT UNSIGNED DEFAULT 0 NOT NULL,
                ADD total INT UNSIGNED DEFAULT NULL,
                ADD progress INT UNSIGNED DEFAULT 0 NOT NULL,
                ADD result JSON DEFAULT NULL',
            TableConstants::JOB_RUN_TABLE,
        ));

        $this->addSql(sprintf('ALTER TABLE %s MODIFY log MEDIUMTEXT DEFAULT NULL', TableConstants::JOB_RUN_TABLE));

        $this->addSql(sprintf(
            'ALTER TABLE %s MODIFY stepNumber INT UNSIGNED DEFAULT NULL',
            TableConstants::ERROR_LOG_TABLE,
        ));

        $this->addSql(sprintf(
            "UPDATE %s SET jobName = JSON_UNQUOTE(JSON_EXTRACT(serializedJob, '$.name'))
                WHERE serializedJob IS NOT NULL AND JSON_VALID(serializedJob)",
            TableConstants::JOB_RUN_TABLE,
        ));

        // A job run of 1.x counted none of its messages, so it would never end.
        $this->addSql(
            sprintf(
                'UPDATE %s SET state = :failed, log = CONCAT_WS(:separator, log, :line) WHERE state = :running',
                TableConstants::JOB_RUN_TABLE,
            ),
            [
                'failed' => JobRunStates::FAILED->value,
                'running' => JobRunStates::RUNNING->value,
                'separator' => "\n",
                'line' => date(DateTimeInterface::ATOM) . ': Stopped by the update to 1.5.',
            ],
        );
    }
}
