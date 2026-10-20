<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261020120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // The SeoBundle creates the table redirects. Without it, the bundle is not installed.
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO `users_permission_definitions` (`key`, `category`)
            VALUES ('site_declarations', 'OpenDxp Seo Bundle')
            SQL);

        $this->addSql(<<<'SQL'
            UPDATE `users`
            SET `permissions` = CONCAT(`permissions`, ',site_declarations')
            WHERE FIND_IN_SET('robots.txt', `permissions`) > 0
                AND FIND_IN_SET('site_declarations', `permissions`) = 0
            SQL);

        // A site saved in the new admin before this migration ran keeps its newer content, unless it is empty.
        $this->addSql(<<<'SQL'
            DELETE `new`
            FROM `settings_store` `new`
            JOIN `settings_store` `old`
                ON `old`.`scope` = 'robots.txt'
                AND `new`.`id` = CONCAT('site_declaration_robots_txt-', SUBSTRING_INDEX(`old`.`id`, '-', -1))
            WHERE `new`.`scope` = 'site_declaration_robots_txt'
                AND TRIM(`new`.`data`) = ''
            SQL);

        $this->addSql(<<<'SQL'
            DELETE `old`
            FROM `settings_store` `old`
            JOIN `settings_store` `new`
                ON `new`.`scope` = 'site_declaration_robots_txt'
                AND `new`.`id` = CONCAT('site_declaration_robots_txt-', SUBSTRING_INDEX(`old`.`id`, '-', -1))
            WHERE `old`.`scope` = 'robots.txt'
            SQL);

        $this->addSql(<<<'SQL'
            UPDATE `settings_store`
            SET
                `id` = CONCAT('site_declaration_robots_txt-', SUBSTRING_INDEX(`id`, '-', -1)),
                `scope` = 'site_declaration_robots_txt'
            WHERE `scope` = 'robots.txt'
            SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
