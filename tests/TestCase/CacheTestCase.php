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

use OpenDxp;
use OpenDxp\Cache;
use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\TestFoundation\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

abstract class CacheTestCase extends TestCase
{
    public const string NAMESPACE = 'test';

    public const int LIFETIME = 2419200;

    /**
     * @var array<string, list<string>>
     */
    public const array SAMPLE_ENTRIES = [
        'A' => ['tag_a', 'tag_ab', 'tag_all'],
        'B' => ['tag_b', 'tag_ab', 'tag_bc', 'tag_all'],
        'C' => ['tag_c', 'tag_bc', 'tag_all'],
    ];

    protected TagAwareAdapterInterface $pool;

    protected WriteLock $lock;

    protected CoreCacheHandler|MockObject $handler;

    /**
     * @param callable(): TagAwareAdapterInterface $pool
     */
    protected function useCachePool(callable $pool, bool $cli = false): void
    {
        $this->pool = $pool();
        $this->pool->clear();

        $this->lock = new WriteLock($this->pool);
        $this->lock->setLogger(new NullLogger());

        // isCli() reads the sapi name. In a test run that is always cli.
        $this->handler = $this->getMockBuilder(CoreCacheHandler::class)
            ->onlyMethods(['isCli'])
            ->setConstructorArgs([$this->pool, $this->lock, OpenDxp::getEventDispatcher()])
            ->getMock();

        $this->handler->method('isCli')->willReturn($cli);
        $this->handler->setLogger(new NullLogger());
    }

    protected function useApplicationCache(): void
    {
        Cache::enable();
        Cache::getHandler()->setHandleCli(true);
        RuntimeCache::clear();
    }

    /**
     * Saving an element clears its cache tag for the whole process. Without taking that back, the
     * element cannot be cached in the same test.
     */
    protected function allowCachingAgain(ElementInterface $element): void
    {
        Cache::getHandler()->removeClearedTags(array_values($element->getCacheTags()));
    }

    protected function queueSampleEntries(): void
    {
        foreach (self::SAMPLE_ENTRIES as $key => $tags) {
            $this->handler->save($key, 'test', $tags);
        }
    }

    /**
     * @return list<string>
     */
    protected function keptEntries(): array
    {
        $keys = array_keys(self::SAMPLE_ENTRIES);

        return array_values(array_filter($keys, fn (string $key) => $this->poolHasItem($key)));
    }

    protected function poolHasItem(string $key): bool
    {
        return $this->pool->getItem($key)->isHit();
    }

    protected function handlerProperty(string $property): mixed
    {
        return (new ReflectionProperty($this->handler, $property))->getValue($this->handler);
    }
}
