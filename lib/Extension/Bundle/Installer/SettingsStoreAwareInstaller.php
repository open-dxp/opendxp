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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Extension\Bundle\Installer;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Tools\SchemaTool;
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

    private ?EntityManagerInterface $schemaEntityManager = null;

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

    /**
     * An application without the Doctrine ORM has no entity manager. The name differs from the entity manager an
     * extending installer may have.
     */
    #[Required]
    public function setSchemaEntityManager(?EntityManagerInterface $entityManager = null): void
    {
        $this->schemaEntityManager = $entityManager;
    }

    protected function getSettingsStoreInstallationId(): string
    {
        return 'BUNDLE_INSTALLED__' . $this->bundle->getNamespace() . '\\' . $this->bundle->getName();
    }

    /**
     * @deprecated since 1.5, will be removed in 2.0. Call markMigrationsAsExecuted() in install() instead.
     */
    public function getLastMigrationVersionClassName(): ?string
    {
        return null;
    }

    /**
     * An installation builds the state that the migrations of the bundle lead to, so none of them may run on it later.
     * Only migrations in the namespace of the bundle count.
     */
    protected function markMigrationsAsExecuted(): void
    {
        $this->completeOwnMigrations(Direction::UP);
    }

    /**
     * Brings the tables of the Doctrine entities in the namespace of the bundle to their mapping,
     * like `doctrine:schema:update` limited to these tables.
     */
    protected function updateEntitySchema(): void
    {
        $entityManager = $this->schemaEntityManager;

        if ($entityManager === null) {
            return;
        }

        $metadata = $this->listEntityMetadata($entityManager);

        if ($metadata === []) {
            return;
        }

        $tables = $this->listOwnTables($metadata);
        $connection = $entityManager->getConnection();
        $configuration = $connection->getConfiguration();
        $previousFilter = $configuration->getSchemaAssetsFilter();

        $configuration->setSchemaAssetsFilter(static fn (string|AbstractAsset $asset): bool => in_array(
            $asset instanceof AbstractAsset ? $asset->getName() : $asset,
            $tables,
            true,
        ));

        try {
            $statements = (new SchemaTool($entityManager))->getUpdateSchemaSql($metadata);
        } finally {
            $configuration->setSchemaAssetsFilter($previousFilter);
        }

        foreach ($statements as $statement) {
            $connection->executeStatement($statement);
        }
    }

    protected function markMigrationsAsNotExecuted(): void
    {
        $this->completeOwnMigrations(Direction::DOWN);
    }

    /**
     * @return list<ClassMetadata<object>>
     */
    private function listEntityMetadata(EntityManagerInterface $entityManager): array
    {
        $namespace = $this->bundle->getNamespace() . '\\';

        return array_values(array_filter(
            $entityManager->getMetadataFactory()->getAllMetadata(),
            static fn (ClassMetadata $class): bool => !$class->isMappedSuperclass
                && !$class->isEmbeddedClass
                && str_starts_with($class->getName(), $namespace),
        ));
    }

    /**
     * The tables of the entities and the join tables of their many-to-many associations,
     * which have no metadata of their own.
     *
     * @param list<ClassMetadata<object>> $metadata
     *
     * @return list<string>
     */
    private function listOwnTables(array $metadata): array
    {
        $tables = [];

        foreach ($metadata as $class) {
            $tables[] = $class->getTableName();

            foreach ($class->getAssociationMappings() as $association) {
                // Doctrine ORM 3 describes an association as an object, Doctrine ORM 2 as an array.
                if ($association instanceof ManyToManyOwningSideMapping) {
                    $tables[] = $association->joinTable->name;
                } elseif (is_array($association) && isset($association['joinTable']['name'])) {
                    $tables[] = $association['joinTable']['name'];
                }
            }
        }

        return array_values(array_unique($tables));
    }

    private function completeOwnMigrations(string $direction): void
    {
        $namespace = $this->bundle->getNamespace() . '\\';

        // The prefix is shared by every user of the repository, so it is cleared again afterwards.
        $this->migrationRepository->setPrefix($namespace);
        $this->tableMetadataStorage->setPrefix($namespace);

        try {
            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $metadataStorage->ensureInitialized();

            $executedMigrations = $metadataStorage->getExecutedMigrations();

            foreach ($this->dependencyFactory->getMigrationRepository()->getMigrations()->getItems() as $migration) {
                $version = $migration->getVersion();
                $executed = $executedMigrations->hasMigration($version);

                if ($direction === Direction::UP ? !$executed : $executed) {
                    $metadataStorage->complete(new ExecutionResult($version, $direction));
                }
            }
        } finally {
            $this->migrationRepository->setPrefix(null);
            $this->tableMetadataStorage->setPrefix(null);
        }
    }

    protected function markInstalled(): void
    {
        $migrationVersion = $this->getLastMigrationVersionClassName();
        if ($migrationVersion) {
            trigger_deprecation(
                'open-dxp/opendxp',
                '1.5',
                'Overriding "%s::getLastMigrationVersionClassName()" is deprecated and will no longer mark migrations in 2.0. Call "markMigrationsAsExecuted()" in "install()" instead.',
                static::class,
            );

            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $this->migrationRepository->setPrefix($this->bundle->getNamespace());
            $this->tableMetadataStorage->setPrefix($this->bundle->getNamespace());
            $migrations = $this->dependencyFactory->getMigrationRepository()->getMigrations();
            $executedMigrations = $metadataStorage->getExecutedMigrations();

            $metadataStorage->ensureInitialized();

            foreach ($migrations->getItems() as $migration) {
                $version = $migration->getVersion();

                if (!$executedMigrations->hasMigration($version)) {
                    $migrationResult = new ExecutionResult($version, Direction::UP);
                    $metadataStorage->complete($migrationResult);
                }

                if ((string)$version === $migrationVersion) {
                    break;
                }
            }

            $this->migrationRepository->setPrefix(null);
            $this->tableMetadataStorage->setPrefix(null);
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

            $metadataStorage->ensureInitialized();

            foreach ($executedMigrations->getItems() as $migration) {
                $migrationResult = new ExecutionResult($migration->getVersion(), Direction::DOWN);
                $metadataStorage->complete($migrationResult);
            }

            $this->tableMetadataStorage->setPrefix(null);
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
