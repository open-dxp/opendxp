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

describe('the save queue', function () {
    it('holds an entry back', function (callable $pool) {
        $this->useCachePool($pool);

        $this->handler->save('itemA', 'test');

        expect($this->poolHasItem('itemA'))->toBeFalse();
    });

    it('writes its entries when it is written', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test');

        $this->handler->writeSaveQueue();

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('is written on shutdown', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test');

        $this->handler->shutdown();

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('forgets its entries once they are written', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test');
        $this->handler->writeSaveQueue();
        $this->pool->deleteItem('itemA');

        $this->handler->writeSaveQueue();

        expect($this->poolHasItem('itemA'))->toBeFalse();
    });

    it('is skipped when the handler writes at once', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setForceImmediateWrite(true);

        $this->handler->save('itemA', 'test');

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('is skipped for an entry the caller forces', function (callable $pool) {
        $this->useCachePool($pool);

        $this->handler->save('itemA', 'test', force: true);

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('writes no more entries than its limit', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setMaxWriteToCacheItems(2);
        $this->handler->save('A', 'test');
        $this->handler->save('B', 'test');
        $this->handler->save('C', 'test');

        $this->handler->writeSaveQueue();

        expect($this->keptEntries())->toBe([
            'A',
            'B',
        ]);
    });

    it('drops the last entry for one with a higher priority', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setMaxWriteToCacheItems(2);
        $this->handler->save('A', 'test');
        $this->handler->save('B', 'test');
        $this->handler->save('C', 'test', priority: 10);

        $this->handler->writeSaveQueue();

        expect($this->keptEntries())->toBe([
            'A',
            'C',
        ]);
    });
})->with('cache pools');
