<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The tables belong to the SEO bundle. A system without the bundle has none of them, and the migration leaves it alone.
 */
final class Version20261003120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Count redirect hits, add domain, protected and scheduled redirects, allow long sources and targets, and key the HTTP error log by a hash of the URI';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('redirects')) {
            $this->upgradeRedirects($schema);
        }

        if ($schema->hasTable('http_error_log') && !$schema->getTable('http_error_log')->hasColumn('uriHash')) {
            $this->upgradeHttpErrorLog();
        }
    }

    public function down(Schema $schema): void
    {
        // do nothing
    }

    private function upgradeRedirects(Schema $schema): void
    {
        $redirects = $schema->getTable('redirects');

        if ($redirects->hasIndex('routing_lookup')) {
            $this->addSql('ALTER TABLE `redirects` DROP INDEX `routing_lookup`');
        }

        $this->addSql('ALTER TABLE `redirects` MODIFY `source` VARCHAR(1024) DEFAULT NULL, MODIFY `target` VARCHAR(1024) DEFAULT NULL');
        $this->addSql("ALTER TABLE `redirects` MODIFY `type` ENUM('entire_uri','path_query','path','auto_create','domain') NOT NULL");

        foreach ([
            'validFrom' => 'INT(11) UNSIGNED DEFAULT NULL AFTER `active`',
            'passThroughPath' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `passThroughParameters`',
            'protected' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`',
        ] as $column => $definition) {
            if (!$redirects->hasColumn($column)) {
                $this->addSql(sprintf('ALTER TABLE `redirects` ADD `%s` %s', $column, $definition));
            }
        }

        // The installer grants the permissions of the bundle. An installed bundle gets the new one here.
        $this->addSql("INSERT IGNORE INTO `users_permission_definitions` (`key`, `category`) VALUES ('redirects_protected', 'OpenDxp Seo Bundle')");

        if (!$redirects->hasIndex('source')) {
            $this->addSql('ALTER TABLE `redirects` ADD INDEX `source` (`source`(191))');
        }

        if (!$schema->hasTable('redirect_hits')) {
            $this->addSql('CREATE TABLE `redirect_hits` (
                `redirectId` int(11) unsigned NOT NULL,
                `hits` bigint(20) unsigned NOT NULL DEFAULT 0,
                `lastHit` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`redirectId`),
                CONSTRAINT `fk_redirect_hits__redirectId` FOREIGN KEY (`redirectId`) REFERENCES `redirects` (`id`) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8mb4');
        }
    }

    /**
     * Rows of the same URI are merged into one before the hash becomes unique.
     */
    private function upgradeHttpErrorLog(): void
    {
        $this->addSql('ALTER TABLE `http_error_log` ADD `uriHash` BINARY(20) DEFAULT NULL AFTER `uri`');
        $this->addSql('UPDATE `http_error_log` SET `uriHash` = UNHEX(SHA1(`uri`))');
        $this->addSql('UPDATE `http_error_log` `entry` JOIN (
                SELECT MAX(`id`) AS `id`, SUM(`count`) AS `total`, MAX(`date`) AS `latest` FROM `http_error_log`
                WHERE `uriHash` IS NOT NULL GROUP BY `uriHash` HAVING COUNT(*) > 1
            ) `merged` ON `entry`.`id` = `merged`.`id`
            SET `entry`.`count` = `merged`.`total`, `entry`.`date` = `merged`.`latest`');
        $this->addSql('DELETE `older` FROM `http_error_log` `older` JOIN `http_error_log` `newer` ON `older`.`uriHash` = `newer`.`uriHash` AND `older`.`id` < `newer`.`id`');
        $this->addSql('ALTER TABLE `http_error_log` ADD UNIQUE INDEX `uriHash` (`uriHash`)');
    }
}
