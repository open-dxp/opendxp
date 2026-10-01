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

namespace OpenDxp\Tests\Support\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Exception;
use OpenDxp\Bundle\InstallBundle\Installer;
use OpenDxp\Cache;
use OpenDxp\Event\TestEvents;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\ClassDefinitionManager;
use OpenDxp\Model\Document;
use OpenDxp\Model\Tool\SettingsStore;
use OpenDxp\Tests\Support\Util\TestHelper;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
class OpenDxp extends Module\Symfony
{
    protected array $groups = [];

    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        // simple unit tests do not need a test DB and run
        // way faster if no DB has to be initialized first, so
        // we enable DB support on a suite level (connect_db)
        $this->config = array_merge($this->config, [
            // skip DB tests flag
            'skip_db_tests' => getenv('OPENDXP_TEST_SKIP_DB'),

            // try to connect to DB
            'connect_db' => false,

            // initialize DB (drop & re-create) - depends on connect_db
            'initialize_db' => true,

            // purge class directory on boot - depends on connect_db
            'purge_class_directory' => true,

            // initializes objects from definitions, only if connect_db and initialize_db
            'setup_objects' => false,
        ]);

        parent::__construct($moduleContainer, $config);
    }

    public function getOpenDxpModule(): OpenDxp|Module
    {
        return $this->getModule('\\' . __CLASS__);
    }

    public function getKernel(): ?KernelInterface
    {
        return $this->kernel;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->kernel->getContainer();
    }

    public function _initialize(): void
    {
        // don't initialize the kernel multiple times if running multiple suites
        // TODO can this lead to side-effects?
        if (null !== $kernel = \OpenDxp::getKernel()) {
            $this->kernel = $kernel;
        } else {
            $this->initializeKernel();
        }

        // connect and initialize DB
        $this->setupDbConnection();

        // initialize system settings
        $this->initializeSystemSettings();

        // disable cache
        Cache::disable();
    }

    /**
     * Initialize the kernel (see parent Symfony module)
     */
    protected function initializeKernel(): void
    {
        $maxNestingLevel = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        //require_once __DIR__ . '/../../../config/constants.php';
        $this->setupOpenDxpDirectories();

        $this->kernel = \OpenDxp\Bootstrap::kernel();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // dispatch kernel booted event - will be used from services which need to reset state between tests
        $this->kernel->getContainer()->get('event_dispatcher')->dispatch(new GenericEvent(), TestEvents::KERNEL_BOOTED);
    }

    protected function setupOpenDxpDirectories(): void
    {
        $directories = [
            OPENDXP_CLASS_DIRECTORY,
            OPENDXP_CLASS_DEFINITION_DIRECTORY,
        ];

        $filesystem = new Filesystem();
        foreach ($directories as $directory) {
            if (!$filesystem->exists($directory)) {
                $filesystem->mkdir($directory, 0755);
            }
        }
    }

    protected function getDbConnection(): Connection
    {
        return $this->getContainer()->get('database_connection');
    }

    protected function getDbName(Connection $connection): string
    {
        return $connection->getParams()['dbname'];
    }

    /**
     * Connect to DB and optionally initialize a new DB
     */
    protected function setupDbConnection(): void
    {
        if (!$this->config['connect_db']) {
            return;
        }

        if ($this->config['skip_db_tests']) {
            $this->debug('[DB] Not connecting to DB as skip_db_tests is set');

            return;
        }

        $connection = $this->getDbConnection();

        if ($this->config['initialize_db']) {
            // (re-)initialize DB
            $connected = $this->initializeDb($connection);
            if ($this->config['setup_objects']) {
                $this->debug('[DB] Initializing objects');
                $this->kernel->getContainer()->get(ClassDefinitionManager::class)->createOrUpdateClassDefinitions();
            }
        } else {
            // just try to connect without initializing the DB
            $this->connectDb($connection);
            $connected = true;
        }

        if ($connected) {
            !defined('OPENDXP_TEST_DB_INITIALIZED') && define('OPENDXP_TEST_DB_INITIALIZED', true);
        }

        if ($this->config['purge_class_directory']) {
            $this->purgeClassDirectory();
        }
    }

    /**
     * Initialize (drop, re-create and setup) the test DB
     *
     * @throws ModuleException
     */
    protected function initializeDb(Connection $connection): bool
    {
        $dbName = $this->getDbName($connection);

        $this->debug(sprintf('[DB] Initializing DB %s', $dbName));

        $connection = $this->getDbConnection();
        $this->dropAndCreateDb($connection);

        $this->connectDb($connection);

        $installer = new Installer($this->getContainer()->get('monolog.logger.opendxp'), $this->getContainer()->get('event_dispatcher'));
        $installer->setImportDatabaseDataDump(false);
        $errors = $installer->setupDatabase($connection, [
            'username' => 'admin',
            'password' => microtime(),
        ]);

        if ($errors) {
            throw new Exception('Setup Database failed: ' . implode("\n", $errors));
        }

        $this->debug(sprintf('[DB] Initialized the test DB %s', $dbName));

        return true;
    }

    /**
     * @throws Exception
     */
    protected function initializeSystemSettings(): void
    {
        if (SettingsStore::get('system_settings')) {
            return;
        }

        $path = TestHelper::resolveFilePath('system_settings.json');
        if (!file_exists($path)) {
            throw new RuntimeException(sprintf('System settings file in %s was not found', $path));
        }
        $data = file_get_contents($path);
        SettingsStore::set('system_settings', $data, 'string', 'opendxp_system_settings');
    }

    /**
     * Drop and re-create the DB
     */
    protected function dropAndCreateDb(Connection $connection): void
    {
        $dbName = $this->getDbName($connection);
        $params = $connection->getParams();
        $config = $connection->getConfiguration();

        unset($params['url']);
        unset($params['dbname']);

        // use a dedicated setup connection as the framework connection is bound to the DB and will
        // fail if the DB doesn't exist
        $setupConnection = DriverManager::getConnection($params, $config);
        $schemaManager = $setupConnection->createSchemaManager();

        $databases = $schemaManager->listDatabases();
        if (in_array($dbName, $databases)) {
            $this->debug(sprintf('[DB] Dropping DB %s', $dbName));
            $schemaManager->dropDatabase($connection->quoteIdentifier($dbName));
        }

        $this->debug(sprintf('[DB] Creating DB %s', $dbName));
        $schemaManager->createDatabase($connection->quoteIdentifier($dbName) . ' charset=utf8mb4');
    }

    /**
     * Try to connect to the DB and set constant if connection was successful.
     */
    protected function connectDb(Connection $connection): void
    {
        try {
            if (!$connection->isConnected()) {
                // doesn't do anything, just to trigger a `->connect()` call (which can't be done directly anymore, because visibility is protected since dbal v4)
                $connection->getNativeConnection();
            }
            $this->debug(sprintf('[DB] Successfully connected to DB %s', $connection->getDatabase()));
        } catch (Exception) {
            $this->debug(sprintf('[DB] Failed to connect to DB %s', $connection->getDatabase()));
        }
    }

    /**
     * Remove and re-create class directory
     */
    protected function purgeClassDirectory(): void
    {
        $directories = [
            OPENDXP_CLASS_DIRECTORY,
            OPENDXP_CLASS_DEFINITION_DIRECTORY,
        ];

        $filesystem = new Filesystem();
        foreach ($directories as $directory) {
            if (file_exists($directory)) {
                $this->debug('[INIT] Purging class directory ' . $directory);

                $filesystem->remove($directory);
                $filesystem->mkdir($directory, 0755);
            }
        }
    }

    public function _before(TestInterface $test): void
    {
        //need to load initialize that service first, before module/symfony does its magic
        $this->grabService(\OpenDxp\Helper\LongRunningHelper::class);

        parent::_before($test);

        $this->groups = $test->getMetadata()->getGroups();

        $this->unsetAdminMode();
    }

    /**
     * Set opendxp into admin state
     */
    public function setAdminMode(): void
    {
        \OpenDxp::setAdminMode();
        Document::setHideUnpublished(false);
        DataObject::setHideUnpublished(false);
        DataObject::setGetInheritedValues(false);
        DataObject\Localizedfield::setGetFallbackValues(false);
    }

    /**
     * Set opendxp into non-admin state
     */
    public function unsetAdminMode(): void
    {
        \OpenDxp::unsetAdminMode();
        Document::setHideUnpublished(true);
        DataObject::setHideUnpublished(true);
        DataObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(true);
    }

    public function makeHtmlSnapshot(?string $name = null): void
    {
        // TODO: Implement makeHtmlSnapshot() method.
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
