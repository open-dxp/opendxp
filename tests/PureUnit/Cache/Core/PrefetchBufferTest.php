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

namespace OpenDxp\Tests\PureUnit\Cache\Core;

use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Pins down the prefetch buffer of {@see CoreCacheHandler}: prefetched hits
 * and known misses are served by the next load() of the same key, and any
 * write, removal, or clear invalidates the buffered entries.
 *
 * @group cache.core.prefetch-buffer
 */
class PrefetchBufferTest extends \Codeception\Test\Unit
{
    protected TagAwareAdapter $cache;

    protected CoreCacheHandler $handler;

    protected function setUp(): void
    {
        $this->cache = new TagAwareAdapter(new ArrayAdapter());
        $this->cache->clear();
        $writeLock = new WriteLock($this->cache);
        $writeLock->setLogger(new NullLogger());
        $this->handler = new CoreCacheHandler($this->cache, $writeLock, new EventDispatcher());
        $this->handler->setLogger(new NullLogger());
        $this->handler->setHandleCli(true);
        $this->handler->setForceImmediateWrite(true);
    }

    public function testPrefetchedHitIsServedByLoad(): void
    {
        $this->handler->save('hitKey', 'hit-data', []);

        $this->handler->prefetch(['hitKey', 'missKey']);

        $this->assertSame('hit-data', $this->handler->load('hitKey'));
        $this->assertFalse($this->handler->load('missKey'), 'A prefetched miss must load as false');
    }

    public function testLoadStillWorksForKeysThatWereNeverPrefetched(): void
    {
        $this->handler->save('plainKey', 'plain-data', []);

        $this->handler->prefetch(['someOtherKey']);

        $this->assertSame('plain-data', $this->handler->load('plainKey'));
    }

    public function testSaveAfterPrefetchInvalidatesBufferedEntry(): void
    {
        $this->handler->prefetch(['freshKey']);

        // the buffered known-miss must not shadow a subsequent write
        $this->handler->save('freshKey', 'fresh-data', []);

        $this->assertSame('fresh-data', $this->handler->load('freshKey'));
    }

    public function testRemoveAfterPrefetchInvalidatesBufferedEntry(): void
    {
        $this->handler->save('removedKey', 'stale-data', []);
        $this->handler->prefetch(['removedKey']);

        $this->handler->remove('removedKey');

        $this->assertFalse($this->handler->load('removedKey'), 'A removed item must not be served from the prefetch buffer');
    }

    public function testClearTagsAfterPrefetchInvalidatesBufferedEntries(): void
    {
        $this->handler->save('taggedKey', 'tagged-data', ['some_tag']);
        $this->handler->prefetch(['taggedKey']);

        $this->handler->clearTags(['some_tag']);

        $this->assertFalse($this->handler->load('taggedKey'), 'Tag-cleared items must not be served from the prefetch buffer');
    }

    public function testPrefetchedEntryIsConsumedOnlyOnce(): void
    {
        $this->handler->save('onceKey', 'first-value', []);
        $this->handler->prefetch(['onceKey']);

        $this->assertSame('first-value', $this->handler->load('onceKey'));

        // after consumption the pool is authoritative again
        $this->handler->save('onceKey', 'second-value', []);
        $this->assertSame('second-value', $this->handler->load('onceKey'));
    }

    public function testInvalidatePrefetchedDropsOnlyTheGivenKeys(): void
    {
        $this->handler->save('batchKey', 'batch-data', []);
        $this->handler->save('otherBatchKey', 'other-batch-data', []);
        $this->handler->prefetch(['batchKey', 'otherBatchKey']);

        // the pool is updated behind the handler's back, so a load can only
        // return the original values while they are still buffered
        foreach (['batchKey' => 'fresh-batch-data', 'otherBatchKey' => 'fresh-other-batch-data'] as $key => $value) {
            $item = $this->cache->getItem($key);
            $item->set($value);
            $this->cache->save($item);
        }

        $this->handler->invalidatePrefetched(['batchKey']);

        $this->assertSame(
            'fresh-batch-data',
            $this->handler->load('batchKey'),
            'invalidated entries must be re-read from the pool'
        );
        $this->assertSame(
            'other-batch-data',
            $this->handler->load('otherBatchKey'),
            'entries of other batches must stay buffered'
        );
    }

    public function testResetDropsBufferedEntries(): void
    {
        $this->handler->save('resetKey', 'stale-data', []);
        $this->handler->prefetch(['resetKey']);

        // another process updates the pool behind the handler's back
        $item = $this->cache->getItem('resetKey');
        $item->set('fresh-data');
        $this->cache->save($item);

        $this->handler->reset();

        $this->assertSame(
            'fresh-data',
            $this->handler->load('resetKey'),
            'reset() must drop buffered entries so the pool is authoritative again'
        );
    }

    public function testHandlerIsResettableBetweenMessengerMessages(): void
    {
        // kernel.reset relies on the handler implementing ResetInterface
        // (picked up by service autoconfiguration)
        $this->assertInstanceOf(ResetInterface::class, $this->handler);
    }
}
