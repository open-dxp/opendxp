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

namespace OpenDxp\Tests\TestCase;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use OpenDxp\Db;
use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use OpenDxp\Migrations\FilteredMigrationsRepository;
use OpenDxp\Migrations\FilteredTableMetadataStorage;
use OpenDxp\Model\Tool\SettingsStore;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\EnvironmentTestCase;
use OpenDxp\Tests\Application\InstallerBundle\Installer;
use OpenDxp\Tests\Application\InstallerBundle\InstallerBundle;
use OpenDxp\Tests\Application\InstallerBundle\SchemaInstaller;

// DDL commits the transaction, so a test that changes the schema cannot be rolled back.
#[SkipDatabaseRollback]
abstract class InstallerTestCase extends EnvironmentTestCase
{
    /**
     * Every table an installer test may create, whether the installer or the test itself creates it.
     */
    private const array TABLES = [
        'installer_bundle_note_tag',
        'installer_bundle_note',
        'installer_bundle_tag',
        'installer_bundle_extension_other',
        'installer_bundle_unrelated',
    ];

    protected static function environment(): string
    {
        return 'installer';
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Db::get()->executeStatement(sprintf('DROP TABLE IF EXISTS %s', $table));
        }

        Db::get()->executeStatement(
            'DELETE FROM migration_versions WHERE version LIKE ?',
            ['OpenDxp\\\\Tests\\\\Application\\\\InstallerBundle%'],
        );

        SettingsStore::delete(
            sprintf('BUNDLE_INSTALLED__%s', InstallerBundle::class),
            'opendxp',
        );

        parent::tearDown();
    }

    protected function migrationInstaller(): Installer
    {
        $installer = new Installer(new InstallerBundle());
        $this->connect($installer);

        return $installer;
    }

    protected function schemaInstaller(): SchemaInstaller
    {
        $installer = new SchemaInstaller(new InstallerBundle());
        $this->connect($installer);
        $installer->setSchemaEntityManager(Container::get(EntityManagerInterface::class));

        return $installer;
    }

    private function connect(SettingsStoreAwareInstaller $installer): void
    {
        $installer->setMigrationRepository(Container::get(FilteredMigrationsRepository::class));
        $installer->setTableMetadataStorage(Container::get(FilteredTableMetadataStorage::class));
        $installer->setDependencyFactory(Container::get(DependencyFactory::class));
    }
}
