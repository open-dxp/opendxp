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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Extension\Bundle\Installer;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use OpenDxp\Migrations\FilteredMigrationsRepository;
use OpenDxp\Migrations\FilteredTableMetadataStorage;
use OpenDxp\Model\Tool\SettingsStore;
use Override;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class SettingsStoreAwareInstaller extends AbstractInstaller
{
    protected FilteredMigrationsRepository $migrationRepository;

    protected FilteredTableMetadataStorage $tableMetadataStorage;

    protected DependencyFactory $dependencyFactory;

    public function __construct(protected BundleInterface $bundle)
    {
        parent::__construct();
    }

    #[Required]
    public function setMigrationRepository(FilteredMigrationsRepository $migrationRepository): void
    {
        $this->migrationRepository = $migrationRepository;
    }

    #[Required]
    public function setTableMetadataStorage(FilteredTableMetadataStorage $tableMetadataStorage): void
    {
        $this->tableMetadataStorage = $tableMetadataStorage;
    }

    #[Required]
    public function setDependencyFactory(DependencyFactory $dependencyFactory): void
    {
        $this->dependencyFactory = $dependencyFactory;
    }

    protected function getSettingsStoreInstallationId(): string
    {
        return 'BUNDLE_INSTALLED__' . $this->bundle->getNamespace() . '\\' . $this->bundle->getName();
    }

    public function getLastMigrationVersionClassName(): ?string
    {
        return null;
    }

    protected function markInstalled(): void
    {
        $migrationVersion = $this->getLastMigrationVersionClassName();
        if ($migrationVersion) {
            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $this->migrationRepository->setPrefix($this->bundle->getNamespace());
            $this->tableMetadataStorage->setPrefix($this->bundle->getNamespace());
            $migrations = $this->dependencyFactory->getMigrationRepository()->getMigrations();
            $executedMigrations = $metadataStorage->getExecutedMigrations();

            foreach ($migrations->getItems() as $migration) {
                $version = $migration->getVersion();

                if (!$executedMigrations->hasMigration($version)) {
                    $migrationResult = new ExecutionResult($version, Direction::UP);
                    $metadataStorage->ensureInitialized();
                    $metadataStorage->complete($migrationResult);
                }

                if ((string)$version === $migrationVersion) {
                    break;
                }
            }
        }

        SettingsStore::set($this->getSettingsStoreInstallationId(), true, SettingsStore::TYPE_BOOLEAN, 'opendxp');
    }

    protected function markUninstalled(): void
    {
        SettingsStore::set($this->getSettingsStoreInstallationId(), false, SettingsStore::TYPE_BOOLEAN, 'opendxp');

        $migrationVersion = $this->getLastMigrationVersionClassName();
        if ($migrationVersion) {
            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $this->tableMetadataStorage->setPrefix($this->bundle->getNamespace());
            $executedMigrations = $metadataStorage->getExecutedMigrations();

            foreach ($executedMigrations->getItems() as $migration) {
                $migrationResult = new ExecutionResult($migration->getVersion(), Direction::DOWN);
                $metadataStorage->ensureInitialized();
                $metadataStorage->complete($migrationResult);
            }
        }
    }

    public function install(): void
    {
        parent::install();
        $this->markInstalled();
    }

    public function uninstall(): void
    {
        parent::uninstall();
        $this->markUninstalled();
    }

    #[Override]
    public function isInstalled(): bool
    {
        $installSetting = SettingsStore::get($this->getSettingsStoreInstallationId(), 'opendxp');

        return (bool) ($installSetting ? $installSetting->getData() : false);
    }

    #[Override]
    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    #[Override]
    public function canBeUninstalled(): bool
    {
        return $this->isInstalled();
    }
}
