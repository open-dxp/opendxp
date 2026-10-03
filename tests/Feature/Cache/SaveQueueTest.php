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


namespace OpenDxp\Tests\Feature\Cache;

use OpenDxp\Tests\TestCase\CacheTestCase;

describe('the save queue', function () {
    it('holds an entry back until the queue is written', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test');

        expect($this->poolHasItem('itemA'))->toBeFalse();

        $this->handler->writeSaveQueue();

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('is written on shutdown', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test');

        expect($this->poolHasItem('itemA'))->toBeFalse();

        $this->handler->shutdown();

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('is empty once it has been written', function (callable $pool) {

        $this->useCachePool($pool);
        $this->queueSampleEntries();

        expect($this->handlerProperty('saveQueue'))->toHaveCount(count(CacheTestCase::SAMPLE_ENTRIES));

        $this->handler->writeSaveQueue();

        expect($this->handlerProperty('saveQueue'))->toBeEmpty();
    });

    it('is skipped when the handler writes immediately', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->setForceImmediateWrite(true);
        $this->handler->save('itemA', 'test');

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('is skipped for an entry the caller forces', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test', [], null, 0, true);

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('takes no more entries than its limit', function (callable $pool) {

        $this->useCachePool($pool);
        $limit = $this->handlerProperty('maxWriteToCacheItems');

        for ($i = 1; $i <= $limit; $i++) {
            $this->handler->save('item_' . $i, $i);
            $this->handler->cleanupQueue();
        }

        $this->handler->save('additional_item', 'foo');
        $this->handler->cleanupQueue();

        expect($this->handlerProperty('saveQueue'))
            ->toHaveCount($limit)
            ->and($this->handlerProperty('saveQueue'))
            ->not->toHaveKey('additional_item');

        $this->handler->writeSaveQueue();

        for ($i = 1; $i <= $limit; $i++) {
            expect($this->poolHasItem('item_' . $i))->toBeTrue();
        }
    });

    it('drops a queued entry for one the caller gives a higher priority', function (callable $pool) {

        $this->useCachePool($pool);
        $limit = $this->handlerProperty('maxWriteToCacheItems');

        for ($i = 1; $i <= $limit; $i++) {
            $this->handler->save('item_' . $i, $i);
            $this->handler->cleanupQueue();
        }

        $this->handler->save('additional_item', 'foo', [], null, 10);
        $this->handler->cleanupQueue();

        expect($this->handlerProperty('saveQueue'))
            ->toHaveCount($limit)
            ->and($this->handlerProperty('saveQueue'))
            ->toHaveKey('additional_item');

        $this->handler->writeSaveQueue();

        expect($this->poolHasItem('additional_item'))->toBeTrue();
    });
})->with('cache pools');
