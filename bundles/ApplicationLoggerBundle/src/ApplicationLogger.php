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

namespace OpenDxp\Bundle\ApplicationLoggerBundle;

use Monolog\Level;
use Monolog\Logger;
use OpenDxp;
use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stringable;
use Throwable;

class ApplicationLogger implements LoggerInterface
{
    protected ?string $component = null;

    protected string|null|FileObject $fileObject = null;

    protected \OpenDxp\Model\DataObject\AbstractObject|\OpenDxp\Model\Document|int|\OpenDxp\Model\Asset|null $relatedObject = null;

    protected string $relatedObjectType = 'object';

    protected array $loggers = [];

    protected static array $instances = [];

    public static function getInstance(string $component = 'default', bool $initDbHandler = false): ApplicationLogger
    {
        $container = OpenDxp::getContainer();
        $containerId = 'opendxp.app_logger.' . $component;

        if ($container->has($containerId)) {
            $logger = $container->get($containerId);
        } else {
            $logger = new self;
            if ($initDbHandler) {
                $logger->addWriter($container->get(ApplicationLoggerDb::class));
            }

            $container->set($containerId, $logger);
        }

        $logger->setComponent($component);

        return $logger;
    }

    public function addWriter(object $writer): void
    {
        if ($writer instanceof \Monolog\Handler\HandlerInterface) {
            if (!isset($this->loggers['default-monolog'])) {
                // auto init Monolog logger
                $this->loggers['default-monolog'] = new Logger('app');
            }
            $this->loggers['default-monolog']->pushHandler($writer);
        } elseif ($writer instanceof \Psr\Log\LoggerInterface) {
            $this->loggers[] = $writer;
        }
    }

    public function setComponent(string $component): void
    {
        $this->component = $component;
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        if (!isset($context['component'])) {
            $context['component'] = $this->component;
        }

        if (!isset($context['fileObject']) && $this->fileObject) {
            $context['fileObject'] = $this->fileObject;
            $this->fileObject = null;
        }

        if (isset($context['fileObject'])) {
            if (is_string($context['fileObject'])) {
                $context['fileObject'] = preg_replace('/^'.preg_quote(\OPENDXP_PROJECT_ROOT, '/').'/', '', $context['fileObject']);
            } elseif ($context['fileObject'] instanceof FileObject) {
                $context['fileObject'] = $context['fileObject']->getFilename();
            } else {
                throw new InvalidArgumentException('fileObject must either be the path to a file as string or an instance of FileObject');
            }
        }

        $relatedObject = null;
        if (isset($context['relatedObject'])) {
            $relatedObject = $context['relatedObject'];
        }

        if (!$relatedObject && $this->relatedObject) {
            $relatedObject = $this->relatedObject;
        }

        if (is_numeric($relatedObject)) {
            $context['relatedObject'] = $relatedObject;
            $context['relatedObjectType'] = $this->relatedObjectType;
        } elseif ($relatedObject instanceof ElementInterface) {
            $context['relatedObject'] = $relatedObject->getId();
            $context['relatedObjectType'] = Service::getElementType($relatedObject);
        }

        if (!isset($context['source'])) {
            $context['source'] = $this->resolveLoggingSource();
        }

        foreach ($this->loggers as $logger) {
            if ($logger instanceof \Psr\Log\LoggerInterface) {
                $logger->log($level, $message, $context);
            }
        }
    }

    /**
     * Resolve logging source
     *
     */
    protected function resolveLoggingSource(): string
    {
        $validMethods = [
            'log', 'logException', 'emergency', 'critical', 'error',
            'alert', 'warning', 'notice', 'info', 'debug',
        ];

        $previousCall = null;
        $logCall = null;

        // look for the first call to this class calling one of the logging methods
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        for ($i = count($backtrace) - 1; $i >= 0; $i--) {
            $previousCall = $logCall;
            $logCall = $backtrace[$i];

            if (!empty($logCall['class']) && $logCall['class'] === self::class && in_array($logCall['function'], $validMethods)) {
                break;
            }
        }

        $normalizeFile = (fn($filename) => str_replace(OPENDXP_PROJECT_ROOT . '/', '', $filename));

        $source = '';
        if (null !== $previousCall) {
            if (isset($previousCall['class'])) {
                // called from a class method
                // ClassName->methodName():line
                $source = sprintf(
                    '%s::%s:%d',
                    $previousCall['class'],
                    $previousCall['function'],
                    $logCall['line']
                );
            } else {
                // called from a function
                // filename.php::functionName():line
                $source = sprintf(
                    '%s::%s:%d',
                    $normalizeFile($previousCall['file']),
                    $previousCall['function'],
                    $logCall['line']
                );
            }
        } else {
            // we don't have a previous call when the logger was directly called
            // from a standalone PHP file (e.g. from a CLI script)
            // filename.php:line
            $source = sprintf(
                '%s:%d',
                $normalizeFile($logCall['file']),
                $logCall['line']
            );
        }

        return $source;
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('emergency', $message, func_get_args());
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('critical', $message, func_get_args());
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('error', $message, func_get_args());
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('alert', $message, func_get_args());
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('warning', $message, func_get_args());
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('notice', $message, func_get_args());
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('info', $message, func_get_args());
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->handleLog('debug', $message, func_get_args());
    }

    protected function handleLog(mixed $level, string $message, array $params): void
    {
        $context = [];

        if (isset($params[1])) {
            if (is_array($params[1])) {
                // standard PSR-3 -> $context is an array
                $context = $params[1];
            } elseif ($params[1] instanceof \OpenDxp\Model\Element\ElementInterface) {
                $context['relatedObject'] = $params[1];
            }
        }

        if (isset($params[2]) && $params[2] instanceof FileObject) {
            $context['fileObject'] = $params[2];
        }

        if (isset($params[3]) && is_string($params[3])) {
            $context['component'] = $params[3];
        }

        $this->log($level, $message, $context);
    }

    public function logException(string $message, Throwable $exceptionObject, ?string $priority = 'alert', ?\OpenDxp\Model\DataObject\AbstractObject $relatedObject = null, ?string $component = null): void
    {
        if (is_null($priority)) {
            $priority = 'alert';
        }

        $message .= ' : '.$exceptionObject->getMessage();

        $fileObject = self::createExceptionFileObject($exceptionObject);

        $this->log($priority, $message, [
            'relatedObject' => $relatedObject,
            'fileObject' => $fileObject,
            'component' => $component,
         ]);
    }

    /**
     * Logs a throwable to a given logger. This can be used to format an exception in the same format
     * as the logException method to any PSR/monolog logger (e.g. when consumed via DI)
     */
    public static function logExceptionObject(
        LoggerInterface $logger,
        string $message,
        Throwable $exception,
        int|string|Level $level = Level::Alert,
        ?\OpenDxp\Model\DataObject\AbstractObject $relatedObject = null,
        array $context = []
    ): void {
        $message .= ' : ' . $exception->getMessage();

        $fileObject = self::createExceptionFileObject($exception);

        $logger->log($level, $message, ['relatedObject' => $relatedObject, 'fileObject' => $fileObject, ...$context]);
    }

    private static function exceptionToString(Throwable $exceptionObject, bool $includeStackTrace, bool $includePrevious = false): string
    {
        $data = [
            $exceptionObject->getMessage(),
            'File: ' . $exceptionObject->getFile(),
            'Line: ' . $exceptionObject->getLine(),
            'Code: ' . $exceptionObject->getCode(),
        ];

        if ($includeStackTrace) {
            $data[] = "Trace:\n" . $exceptionObject->getTraceAsString();
        }

        if ($includePrevious && $exceptionObject->getPrevious()) {
            $data[] = "\nPrevious:\n" . self::exceptionToString($exceptionObject->getPrevious(), $includeStackTrace);
        }

        return implode("\n", $data);
    }

    private static function createExceptionFileObject(Throwable $exceptionObject): FileObject
    {
        $data = self::exceptionToString($exceptionObject, true, true);

        return new FileObject($data);
    }
}
