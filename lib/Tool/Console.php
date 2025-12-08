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

namespace OpenDxp\Tool;

use COM;
use Exception;
use OpenDxp;
use OpenDxp\Config;
use OpenDxp\Logger;
use OpenDxp\Model\Exception\NotFoundException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class Console
{
    private static ?string $systemEnvironment = null;

    protected static array $executableCache = [];

    /**
     * @return string "windows" or "unix"
     */
    private static function getSystemEnvironment(): string
    {
        if (self::$systemEnvironment == null) {
            if (stripos(php_uname('s'), 'windows') !== false) {
                self::$systemEnvironment = 'windows';
            } elseif (stripos(php_uname('s'), 'darwin') !== false) {
                self::$systemEnvironment = 'darwin';
            } else {
                self::$systemEnvironment = 'unix';
            }
        }

        return self::$systemEnvironment;
    }

    /**
     * @return string|false ($throwException is true ? string : string|false)
     *
     * @throws Exception
     */
    public static function getExecutable(string $name, bool $throwException = false, bool $checkExternal = true): string|false
    {
        if (isset(self::$executableCache[$name])) {
            if (!self::$executableCache[$name] && $throwException) {
                throw new Exception("No '$name' executable found, please install the application or add it to the PATH (in system settings or to your PATH environment variable");
            }

            return self::$executableCache[$name];
        }

        // allow custom setup routines for certain programs
        $customSetupMethod = 'setup' . ucfirst($name);
        if (method_exists(self::class, $customSetupMethod)) {
            self::$customSetupMethod();
        }

        // get executable from opendxp_executable_* param
        if ($checkExternal && $externalExecutable = self::getExternalExecutable($name, $throwException)) {
            self::$executableCache[$name] = $externalExecutable;

            return $externalExecutable;
        }

        $paths = [];

        try {
            $systemConfig = Config::getSystemConfiguration('general');
            if (!empty($systemConfig['path_variable'])) {
                $paths = explode(PATH_SEPARATOR, $systemConfig['path_variable']);
            }
        } catch (Exception $e) {
            Logger::warning((string) $e);
        }

        $paths[] = '';

        // allow custom check routines for certain programs
        $customCheckMethod = 'check' . ucfirst($name);
        if (!method_exists(self::class, $customCheckMethod)) {
            $customCheckMethod = null;
        }

        foreach ($paths as $path) {
            try {
                $path = rtrim($path, '/\\ ');
                $executablePath = $path ? $path . DIRECTORY_SEPARATOR . $name : $name;

                $executableFinder = new ExecutableFinder();
                $fullQualifiedPath = $executableFinder->find($executablePath);
                if ($fullQualifiedPath && (!$customCheckMethod || self::$customCheckMethod($executablePath))) {
                    self::$executableCache[$name] = $fullQualifiedPath;
                    return $fullQualifiedPath;
                }
            } catch (Exception) {
                // nothing to do ...
            }
        }

        self::$executableCache[$name] = false;

        if ($throwException) {
            throw new Exception("No '$name' executable found, please install the application or add it to the PATH (in system settings or to your PATH environment variable");
        }

        return false;
    }

    private static function getExternalExecutable(string $name, bool $throwException = false): string|false
    {
        $executable = false;

        // use DI to provide the ability to customize / overwrite paths
        if (OpenDxp::hasContainer() && OpenDxp::getContainer()->hasParameter('opendxp_executable_' . $name)) {
            $executable = OpenDxp::getContainer()->getParameter('opendxp_executable_' . $name);

            if ($executable === false && $throwException) {
                throw new Exception("'$name' executable was disabled manually in parameters.yml");
            }
        }

        return $executable;
    }

    /**
     * @throws Exception
     */
    public static function getPhpCli(): string
    {
        try {
            $phpPath = self::getExternalExecutable('php', true);
            if ($phpPath) {
                return $phpPath;
            }

            $phpFinder = new PhpExecutableFinder();
            $phpPath = $phpFinder->find(true);
            if (!$phpPath) {
                throw new NotFoundException('No PHP executable found, get from getExecutable()');
            }
        } catch (Exception) {
            $phpPath = self::getExecutable('php', true, false);
        }

        return $phpPath;
    }

    /**
     * @throws Exception
     */
    public static function getTimeoutBinary(): string|false
    {
        return self::getExecutable('timeout');
    }

    /**
     * @param string[] $arguments
     *
     * @return string[]
     */
    protected static function buildPhpScriptCmd(string $script, array $arguments = []): array
    {
        $phpCli = self::getPhpCli();

        $cmd = [$phpCli, $script];
        if (Config::getEnvironment()) {
            $cmd[] = '--env=' . Config::getEnvironment();
        }

        return [...$cmd, ...$arguments];
    }

    /**
     * @param string[] $arguments
     */
    public static function runPhpScript(string $script, array $arguments = [], ?string $outputFile = null, float $timeout = 60): string
    {
        $cmd = self::buildPhpScriptCmd($script, $arguments);
        self::addLowProcessPriority($cmd);
        $process = new Process($cmd);

        $process->setTimeout($timeout);

        $process->start();

        if (!empty($outputFile)) {
            $logHandle = fopen($outputFile, 'a');
            $process->wait(function ($type, $buffer) use ($logHandle): void {
                fwrite($logHandle, $buffer);
            });
            fclose($logHandle);
        } else {
            $process->wait();
        }

        return $process->getOutput();
    }

    public static function execInBackground(string $cmd, ?string $outputFile = null): int
    {
        // windows systems
        if (self::getSystemEnvironment() === 'windows') {
            return self::execInBackgroundWindows($cmd, $outputFile);
        }
        // windows systems
        if (self::getSystemEnvironment() === 'darwin') {
            return self::execInBackgroundUnix($cmd, $outputFile, false);
        }
        return self::execInBackgroundUnix($cmd, $outputFile);
    }

    private static function execInBackgroundUnix(string $cmd, ?string $outputFile, bool $useNohup = true): int
    {
        if (!$outputFile) {
            $outputFile = '/dev/null';
        }

        $nice = (string) self::getExecutable('nice');
        if ($nice) {
            $nice .= ' -n 19 ';
        }

        if ($useNohup) {
            $nohup = (string) self::getExecutable('nohup');
            if ($nohup) {
                $nohup .= ' ';
            }
        } else {
            $nohup = '';
        }

        /**
         * mod_php seems to lose the environment variables if we do not set them manually before the child process is started
         */
        if (str_contains(php_sapi_name(), 'apache')) {
            foreach (['APP_ENV'] as $envVarName) {
                if ($envValue = $_SERVER[$envVarName] ?? $_SERVER['REDIRECT_' . $envVarName] ?? null) {
                    putenv($envVarName . '='.$envValue);
                }
            }
        }

        $commandWrapped = $nohup . $nice . $cmd . ' > '. $outputFile .' 2>&1 & echo $!';
        Logger::debug('Executing command `' . $commandWrapped . '´ on the current shell in background');
        $pid = shell_exec($commandWrapped);

        Logger::debug('Process started with PID ' . $pid);

        return (int)$pid;
    }

    private static function execInBackgroundWindows(string $cmd, ?string $outputFile): int
    {
        if (!$outputFile) {
            $outputFile = 'NUL';
        }

        $commandWrapped = 'cmd /c ' . $cmd . ' > '. $outputFile . ' 2>&1';
        Logger::debug('Executing command `' . $commandWrapped . '´ on the current shell in background');

        $WshShell = new COM('WScript.Shell');
        $WshShell->Run($commandWrapped, 0, false);
        Logger::debug('Process started - returning the PID is not supported on Windows Systems');

        return 0;
    }

    /**
     * @param string[]|string $cmd
     *
     * @internal
     */
    public static function addLowProcessPriority(array|string &$cmd): void
    {
        $nice = (string) self::getExecutable('nice');
        if ($nice) {
            if (is_string($cmd)) {
                $cmd = $nice . ' -n 19 ' . $cmd;
            } elseif (is_array($cmd)) {
                array_unshift($cmd, $nice, '-n', '19');
            }
        }
    }
}
