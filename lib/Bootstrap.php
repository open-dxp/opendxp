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

namespace OpenDxp;

use const PHP_SAPI;
use InvalidArgumentException;
use OpenDxp;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Tool\MaintenanceModeHelperInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class Bootstrap
{
    /**
     * @internal
     */
    public static bool $isInstaller = false;

    public static function startup(): Kernel|\App\Kernel|KernelInterface
    {
        self::setProjectRoot();
        self::bootstrap();

        return self::kernel();
    }

    public static function startupCli(): Kernel|KernelInterface
    {
        // ensure the cli arguments are set
        if (!isset($_SERVER['argv'])) {
            $_SERVER['argv'] = [];
        }

        self::setProjectRoot();
        self::bootstrap();

        $workingDirectory = getcwd();
        chdir(__DIR__);

        // init shell verbosity as 0 - this would normally be handled by the console application,
        // but as we boot the kernel early the kernel initializes this to 3 (verbose) by default
        putenv('SHELL_VERBOSITY=0');
        $_ENV['SHELL_VERBOSITY'] = 0;
        $_SERVER['SHELL_VERBOSITY'] = 0;

        /** @var Kernel $kernel */
        $kernel = self::kernel();

        if (is_readable($workingDirectory)) {
            chdir($workingDirectory);
        }

        // activate inheritance for cli-scripts
        OpenDxp::unsetAdminMode();
        Document::setHideUnpublished(true);
        DataObject::setHideUnpublished(true);
        DataObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(true);

        // OpenDxp\Console handles maintenance mode through the AbstractCommand
        $openDxpConsole = (defined('OPENDXP_CONSOLE') && true === OPENDXP_CONSOLE);
        if (!$openDxpConsole) {
            $maintenanceModeHelper = $kernel->getContainer()->get(MaintenanceModeHelperInterface::class);
            // skip if maintenance mode is on and the flag is not set
            if (($maintenanceModeHelper->isActive()) &&
                !in_array('--ignore-maintenance-mode', $_SERVER['argv'])) {
                die("in maintenance mode -> skip\nset the flag --ignore-maintenance-mode to force execution\n");
            }
        }

        return $kernel;
    }

    public static function setProjectRoot(): void
    {
        // this should already be defined at this point, but we include a fallback for backwards compatibility here
        if (!defined('OPENDXP_PROJECT_ROOT')) {
            define(
                'OPENDXP_PROJECT_ROOT',
                $_SERVER['OPENDXP_PROJECT_ROOT'] ?? $_ENV['OPENDXP_PROJECT_ROOT'] ??
                $_SERVER['REDIRECT_OPENDXP_PROJECT_ROOT'] ?? $_ENV['REDIRECT_OPENDXP_PROJECT_ROOT'] ??
                realpath(__DIR__ . '/../../../..')
            );
        }
    }

    public static function bootstrap(): void
    {
        $isCli = in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true);

        // Installer
        // Keep this block unless core is requiring symfony runtime as mandatory and opendxp-install is adapted
        if ($isCli && !isset($_ENV['SYMFONY_DOTENV_VARS']) && self::$isInstaller) {
            self::bootDotEnvVariables();
        }

        self::defineConstants();

        // load a startup file if it exists - this is a good place to preconfigure the system
        // before the kernel is loaded - e.g. to set trusted proxies on the request object
        $startupFile = OPENDXP_PROJECT_ROOT . '/config/opendxp/startup.php';
        if (file_exists($startupFile)) {
            include_once $startupFile;
        }

        if (false === $isCli) {
            self::setTrustedProxies();
        }
    }

    /**
     * @deprecated only for compatibility reasons, will be removed in OpenDxp 12
     */
    private static function bootDotEnvVariables(): void
    {
        if (class_exists('Symfony\Component\Dotenv\Dotenv')) {
            (new Dotenv())->bootEnv(OPENDXP_PROJECT_ROOT . '/.env');
        }
    }

    private static function setTrustedProxies(): void
    {
        // see https://github.com/symfony/recipes/blob/master/symfony/framework-bundle/4.2/public/index.php#L15
        if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
            Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        }
        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
            Request::setTrustedHosts([$trustedHosts]);
        }
    }

    public static function defineConstants(): void
    {
        // make sure $_SERVER contains all values of $_ENV
        $_SERVER += $_ENV;

        // load custom constants
        $customConstantsFile = OPENDXP_PROJECT_ROOT . '/config/opendxp/constants.php';
        if (file_exists($customConstantsFile)) {
            include_once $customConstantsFile;
        }

        $resolveConstant = static function (string $name, $default, bool $define = true) {
            // return constant if defined
            if (defined($name)) {
                return constant($name);
            }

            $value = $_SERVER[$name] ?? $default;
            if ($define) {
                define($name, $value);
            }

            return $value;
        };

        // basic paths
        $resolveConstant('OPENDXP_COMPOSER_PATH', OPENDXP_PROJECT_ROOT . '/vendor');
        $resolveConstant('OPENDXP_COMPOSER_FILE_PATH', OPENDXP_PROJECT_ROOT);
        $resolveConstant('OPENDXP_PATH', realpath(__DIR__ . '/..'));
        $resolveConstant('OPENDXP_WEB_ROOT', OPENDXP_PROJECT_ROOT . '/public');
        $resolveConstant('OPENDXP_PRIVATE_VAR', OPENDXP_PROJECT_ROOT . '/var');

        // special directories for tests
        // test mode can bei either controlled by a constant or an env variable
        $testMode = (bool)$resolveConstant('OPENDXP_TEST', false, false);
        if ($testMode) {
            // override and initialize directories
            $resolveConstant('OPENDXP_CLASS_DIRECTORY', OPENDXP_PATH . '/tests/_output/var/classes');

            if (!defined('OPENDXP_TEST')) {
                define('OPENDXP_TEST', true);
            }
        }

        // paths relying on basic paths above
        $resolveConstant('OPENDXP_CUSTOM_CONFIGURATION_DIRECTORY', OPENDXP_PROJECT_ROOT . '/config/opendxp');
        $resolveConstant('OPENDXP_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY', OPENDXP_CUSTOM_CONFIGURATION_DIRECTORY . '/classes');
        $resolveConstant('OPENDXP_CONFIGURATION_DIRECTORY', OPENDXP_PRIVATE_VAR . '/config');
        $resolveConstant('OPENDXP_LOG_DIRECTORY', OPENDXP_PRIVATE_VAR . '/log');
        $resolveConstant('OPENDXP_CACHE_DIRECTORY', OPENDXP_PRIVATE_VAR . '/cache/opendxp');
        $resolveConstant('OPENDXP_SYMFONY_CACHE_DIRECTORY', OPENDXP_PRIVATE_VAR . '/cache');
        $resolveConstant('OPENDXP_CLASS_DIRECTORY', OPENDXP_PRIVATE_VAR . '/classes');
        $resolveConstant('OPENDXP_CLASS_DEFINITION_DIRECTORY', OPENDXP_CLASS_DIRECTORY);
        $resolveConstant('OPENDXP_SYSTEM_TEMP_DIRECTORY', OPENDXP_PRIVATE_VAR . '/tmp');

        // configure PHP's error logging
        $resolveConstant('OPENDXP_KERNEL_CLASS', '\App\Kernel');
    }

    public static function kernel(): Kernel|\App\Kernel|KernelInterface
    {
        $environment = Config::getEnvironment();

        $debug = (bool) ($_SERVER['APP_DEBUG'] ?? false);
        if ($debug) {
            umask(0000);
            Debug::enable();
        }

        $kernelClass = defined('OPENDXP_KERNEL_CLASS') ? OPENDXP_KERNEL_CLASS : '\App\Kernel';

        if (!class_exists($kernelClass)) {
            throw new InvalidArgumentException(sprintf('Defined Kernel Class %s not found', $kernelClass));
        }

        if (!is_subclass_of($kernelClass, Kernel::class)) {
            throw new InvalidArgumentException(sprintf('Defined Kernel Class %s needs to extend the \OpenDxp\Kernel Class', $kernelClass));
        }

        $kernel = new $kernelClass($environment, $debug);
        OpenDxp::setKernel($kernel);
        $kernel->boot();

        $conf = Config::getSystemConfiguration();

        if ($conf['general']['timezone']) {
            date_default_timezone_set($conf['general']['timezone']);
        }

        return $kernel;
    }
}
