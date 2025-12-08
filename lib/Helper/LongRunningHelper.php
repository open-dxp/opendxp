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

namespace OpenDxp\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Exception;
use LogicException;
use Monolog\Handler\HandlerInterface;
use OpenDxp\Cache\RuntimeCache;
use Psr\Log\LoggerAwareTrait;

final class LongRunningHelper
{
    use LoggerAwareTrait;

    /**
     * @var string[]
     */
    protected array $openDxpRuntimeCacheProtectedItems = [
        'Config_system',
        'opendxp_admin_user',
        'Config_website',
        'opendxp_error_document',
        'opendxp_site',
        'OpenDxp_Db',
    ];

    protected array $monologHandlers = [];

    /**
     * @var string[]
     */
    protected array $tmpFilePaths = [];

    /**
     * LongRunningHelper constructor.
     *
     */
    public function __construct(protected ConnectionRegistry $connectionRegistry)
    {
    }

    public function cleanUp(array $options = []): void
    {
        $this->cleanupDoctrine();
        $this->cleanupMonolog();
        $this->cleanupOpenDxpRuntimeCache($options);
        $this->triggerPhpGarbageCollector();
    }

    protected function cleanupDoctrine(): void
    {
        try {
            foreach ($this->connectionRegistry->getConnections() as $connection) {
                if (!($connection instanceof Connection)) {
                    throw new LogicException('Expected only instances of Connection');
                }
                if ($connection->isTransactionActive() === false) {
                    $connection->close();
                }
            }
        } catch (Exception) {
            // connection couldn't be established, this is e.g. the case when OpenDxp isn't installed yet
        }
    }

    protected function triggerPhpGarbageCollector(): void
    {
        gc_enable();
        $collectedCycles = gc_collect_cycles();

        $this->logger->debug(sprintf('PHP garbage collector collected %d cycles', $collectedCycles));
    }

    protected function cleanupOpenDxpRuntimeCache(array $options = []): void
    {
        $options = $this->resolveOptions(__METHOD__, $options);

        $protectedItems = $this->openDxpRuntimeCacheProtectedItems;

        if (isset($options['keepItems']) && is_array($options['keepItems']) && count($options['keepItems']) > 0) {
            $protectedItems = [...$protectedItems, ...$options['keepItems']];
        }

        RuntimeCache::clear($protectedItems);
    }

    public function addOpenDxpRuntimeCacheProtectedItems(array $items): void
    {
        $this->openDxpRuntimeCacheProtectedItems = [...$this->openDxpRuntimeCacheProtectedItems, ...$items];
        $this->openDxpRuntimeCacheProtectedItems = array_unique($this->openDxpRuntimeCacheProtectedItems);
    }

    public function removeOpenDxpRuntimeCacheProtectedItems(): void
    {
        foreach ($this->openDxpRuntimeCacheProtectedItems as $item) {
            $key = array_search($item, $this->openDxpRuntimeCacheProtectedItems);
            if ($key !== false) {
                unset($this->openDxpRuntimeCacheProtectedItems[$key]);
            }
        }
    }

    protected function cleanupMonolog(): void
    {
        foreach ($this->monologHandlers as $handler) {
            $handler->close();
        }
    }

    /**
     * @internal
     *
     */
    public function addMonologHandler(HandlerInterface $handler): void
    {
        $this->monologHandlers[] = $handler;
    }

    protected function resolveOptions(string $method, array $options): array
    {
        $name = preg_replace('@[^\:]+\:\:cleanup@', '', $method);
        $name = lcfirst($name);

        return $options[$name] ?? [];
    }

    /**
     * @internal
     * Register a temp file which will be deleted on next call of cleanUp()
     */
    public function addTmpFilePath(string $tmpFilePath): void
    {
        $this->tmpFilePaths[] = $tmpFilePath;
    }

    public function deleteTemporaryFiles(): void
    {
        foreach ($this->tmpFilePaths as $tmpFilePath) {
            @unlink($tmpFilePath);
        }
        $this->tmpFilePaths = [];
    }
}
