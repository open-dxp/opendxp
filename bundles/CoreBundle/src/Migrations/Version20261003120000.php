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
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend the redirects and keep a single row per URI in the HTTP error log';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('redirects')) {
            $redirects = $schema->getTable('redirects');

            $this->extendRedirects($redirects);
            $this->addProtectedPermission();
            $this->addSourceIndex($redirects);
        }

        if ($schema->hasTable('redirects') && !$schema->hasTable('redirect_hits')) {
            $this->createRedirectHits();
        }

        if ($schema->hasTable('http_error_log') && !$schema->getTable('http_error_log')->hasColumn('uriHash')) {
            $this->addUriHash();
            $this->mergeDuplicateUris();
            $this->addSql('ALTER TABLE `http_error_log` ADD UNIQUE INDEX `uriHash` (`uriHash`)');
        }
    }

    public function down(Schema $schema): void
    {
    }

    private function extendRedirects(Table $redirects): void
    {
        if ($redirects->hasIndex('routing_lookup')) {
            $this->addSql('ALTER TABLE `redirects` DROP INDEX `routing_lookup`');
        }

        $this->addSql(<<<'SQL'
            ALTER TABLE `redirects`
                MODIFY `source` VARCHAR(1024) DEFAULT NULL,
                MODIFY `target` VARCHAR(1024) DEFAULT NULL,
                MODIFY `type` ENUM('entire_uri', 'path_query', 'path', 'auto_create', 'domain') NOT NULL
            SQL);

        $columns = [
            'validFrom' => 'INT(11) UNSIGNED DEFAULT NULL AFTER `active`',
            'passThroughPath' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `passThroughParameters`',
            'protected' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`',
        ];

        foreach ($columns as $column => $definition) {
            if (!$redirects->hasColumn($column)) {
                $this->addSql(sprintf('ALTER TABLE `redirects` ADD `%s` %s', $column, $definition));
            }
        }
    }

    private function addProtectedPermission(): void
    {
        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO `users_permission_definitions` (`key`, `category`)
            VALUES ('redirects_protected', 'OpenDxp Seo Bundle')
            SQL);
    }

    private function addSourceIndex(Table $redirects): void
    {
        if (!$redirects->hasIndex('source')) {
            $this->addSql('ALTER TABLE `redirects` ADD INDEX `source` (`source`(191))');
        }
    }

    private function createRedirectHits(): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE `redirect_hits` (
                `redirectId` INT(11) UNSIGNED NOT NULL,
                `hits` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                `lastHit` INT(11) UNSIGNED DEFAULT NULL,
                PRIMARY KEY (`redirectId`),
                CONSTRAINT `fk_redirect_hits__redirectId`
                    FOREIGN KEY (`redirectId`) REFERENCES `redirects` (`id`) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8mb4
            SQL);
    }

    private function addUriHash(): void
    {
        $this->addSql('ALTER TABLE `http_error_log` ADD `uriHash` BINARY(20) DEFAULT NULL AFTER `uri`');
        $this->addSql('UPDATE `http_error_log` SET `uriHash` = UNHEX(SHA1(`uri`))');
    }

    /**
     * The newest row of a URI keeps the sum of all its counts and the latest date. The other rows go.
     * Both statements join the log with the duplicates only, because `uriHash` has no index yet.
     */
    private function mergeDuplicateUris(): void
    {
        $duplicates = <<<'SQL'
            SELECT
                `uriHash`,
                MAX(`id`) AS `id`,
                SUM(`count`) AS `total`,
                MAX(`date`) AS `latest`
            FROM `http_error_log`
            WHERE `uriHash` IS NOT NULL
            GROUP BY `uriHash`
            HAVING COUNT(*) > 1
            SQL;

        $this->addSql(<<<SQL
            UPDATE `http_error_log` `entry`
            JOIN ({$duplicates}) `duplicate` ON `entry`.`id` = `duplicate`.`id`
            SET
                `entry`.`count` = `duplicate`.`total`,
                `entry`.`date` = `duplicate`.`latest`
            SQL);

        $this->addSql(<<<SQL
            DELETE `entry`
            FROM `http_error_log` `entry`
            JOIN ({$duplicates}) `duplicate`
                ON `entry`.`uriHash` = `duplicate`.`uriHash`
                AND `entry`.`id` < `duplicate`.`id`
            SQL);
    }
}
