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


namespace OpenDxp\Tests\PHPUnit;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;

final class CreateMissingTables implements Extension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

        if (!is_string($url) || $url === '') {
            throw new RuntimeException('DATABASE_URL names no database, so the missing tables cannot be created.');
        }

        // dama/doctrine-test-bundle wraps a test in a transaction, but only on the connections it hands out itself.
        // This one is built directly, so the CREATE TABLE commits nothing.
        $connection = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql']))->parse($url));

        $schema = $connection->createSchemaManager();

        if (!$schema->tablesExist(['cache_items'])) {
            (new DoctrineDbalAdapter($connection))->createTable();
        }

        // DatabaseVersionStorageAdapter writes to versionsData, and nothing in core creates it.
        // It is joined to versions on ctype, so both need the same collation.
        if (!$schema->tablesExist(['versionsData'])) {
            $collation = $connection->fetchOne(
                'SELECT TABLE_COLLATION FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                ['versions'],
            );

            $connection->executeStatement(sprintf(
                'CREATE TABLE `versionsData` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `cid` int(11) unsigned DEFAULT NULL,
                    `ctype` enum(\'document\',\'asset\',\'object\') DEFAULT NULL,
                    `metaData` longblob DEFAULT NULL,
                    `binaryData` longblob DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) COLLATE %s',
                $collation,
            ));
        }

        $connection->close();
    }
}
