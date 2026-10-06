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

use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use OpenDxp\Db;
use OpenDxp\Migrations\FilteredMigrationsRepository;
use OpenDxp\Migrations\FilteredTableMetadataStorage;
use OpenDxp\Model\Tool\SettingsStore;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Application\InstallerBundle\Installer;
use OpenDxp\Tests\Application\InstallerBundle\InstallerBundle;
use OpenDxp\Tests\Application\InstallerBundle\SchemaInstaller;

const INSTALLER_ENTITY_TABLES = [
    'installer_bundle_note_tag',
    'installer_bundle_note',
    'installer_bundle_tag',
    'installer_bundle_extension_other',
    'installer_bundle_unrelated',
];

function migrationInstaller(): Installer
{
    $installer = new Installer(new InstallerBundle());
    $installer->setMigrationRepository(Container::get(FilteredMigrationsRepository::class));
    $installer->setTableMetadataStorage(Container::get(FilteredTableMetadataStorage::class));
    $installer->setDependencyFactory(Container::get(DependencyFactory::class));

    return $installer;
}

function schemaInstaller(): SchemaInstaller
{
    $installer = new SchemaInstaller(new InstallerBundle());
    $installer->setMigrationRepository(Container::get(FilteredMigrationsRepository::class));
    $installer->setTableMetadataStorage(Container::get(FilteredTableMetadataStorage::class));
    $installer->setDependencyFactory(Container::get(DependencyFactory::class));
    $installer->setSchemaEntityManager(Container::get(EntityManagerInterface::class));

    return $installer;
}

/**
 * @return list<string>
 */
function executedTestMigrations(): array
{
    return Db::get()->fetchFirstColumn(
        'SELECT version FROM migration_versions WHERE version LIKE ? ORDER BY version',
        ['OpenDxp\\\\Tests\\\\Application\\\\InstallerBundle%'],
    );
}

/**
 * @return list<string>
 */
function availableMigrations(): array
{
    return array_map(
        static fn ($migration): string => (string) $migration->getVersion(),
        Container::get(DependencyFactory::class)->getMigrationRepository()->getMigrations()->getItems(),
    );
}

/**
 * @return list<string>
 */
function columnsOf(string $table): array
{
    return array_keys(Db::get()->createSchemaManager()->listTableColumns($table));
}

function tableExists(string $table): bool
{
    return Db::get()->createSchemaManager()->tablesExist([$table]);
}

function forgetTestInstallation(): void
{
    foreach (INSTALLER_ENTITY_TABLES as $table) {
        Db::get()->executeStatement(sprintf('DROP TABLE IF EXISTS %s', $table));
    }

    Db::get()->executeStatement(
        'DELETE FROM migration_versions WHERE version LIKE ?',
        ['OpenDxp\\\\Tests\\\\Application\\\\InstallerBundle%'],
    );

    SettingsStore::delete('BUNDLE_INSTALLED__OpenDxp\\Tests\\Application\\InstallerBundle\\InstallerBundle', 'opendxp');
}
