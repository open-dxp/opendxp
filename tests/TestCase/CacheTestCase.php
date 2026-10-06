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

use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use OpenDxp\TestFoundation\TestCase;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

abstract class CacheTestCase extends TestCase
{
    public const string NAMESPACE = 'test';

    public const int LIFETIME = 2419200;

    /**
     * @var array<string, list<string>>
     */
    public const array SAMPLE_ENTRIES = [
        'A' => [
            'tag_a',
            'tag_ab',
            'tag_all',
        ],
        'B' => [
            'tag_b',
            'tag_ab',
            'tag_bc',
            'tag_all',
        ],
        'C' => [
            'tag_c',
            'tag_bc',
            'tag_all',
        ],
    ];

    protected TagAwareAdapterInterface $pool;

    protected WriteLock $lock;

    protected CoreCacheHandler $handler;

    /**
     * @param callable(): TagAwareAdapterInterface $pool
     */
    protected function useCachePool(callable $pool): void
    {
        $this->pool = $pool();
        $this->pool->clear();

        $this->handler = cacheHandler($this->pool);
        $this->lock = $this->handler->getWriteLock();
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

        $kept = array_filter(
            $keys,
            fn (string $key) => $this->poolHasItem($key),
        );

        return array_values($kept);
    }

    protected function poolHasItem(string $key): bool
    {
        return $this->pool->getItem($key)->isHit();
    }
}
