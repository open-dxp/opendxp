<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
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

        $this->addSql(sprintf(
            'ALTER TABLE %s MODIFY stepNumber INT UNSIGNED DEFAULT NULL',
            TableConstants::ERROR_LOG_TABLE,
        ));

        $this->addSql(sprintf(
            "UPDATE %s SET jobName = JSON_UNQUOTE(JSON_EXTRACT(serializedJob, '$.name'))
                WHERE serializedJob IS NOT NULL AND JSON_VALID(serializedJob)",
            TableConstants::JOB_RUN_TABLE,
        ));
    }
}
