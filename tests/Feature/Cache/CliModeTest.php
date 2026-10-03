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

describe('a handler running on the command line', function () {
    it('writes nothing, whether the queue is written or not', function (callable $pool) {

        $this->useCachePool($pool, cli: true);

        expect($this->handler->save('itemA', 'test'))->toBeFalse();

        $this->handler->writeSaveQueue();

        expect($this->poolHasItem('itemA'))->toBeFalse();
    });

    it('writes nothing even when it writes immediately', function (callable $pool) {

        $this->useCachePool($pool, cli: true);
        $this->handler->setForceImmediateWrite(true);

        expect($this->handler->save('itemA', 'test'))
            ->toBeFalse()
            ->and($this->poolHasItem('itemA'))
            ->toBeFalse();
    });

    it('writes an entry the caller forces', function (callable $pool) {

        $this->useCachePool($pool, cli: true);

        expect($this->handler->save('itemA', 'test', [], null, 0, true))
            ->toBeTrue()
            ->and($this->poolHasItem('itemA'))
            ->toBeTrue();
    });

    it('writes once it is told to handle the command line', function (callable $pool) {

        $this->useCachePool($pool, cli: true);
        $this->handler->setHandleCli(true);

        expect($this->handler->save('itemA', 'test'))
            ->toBeTrue()
            ->and($this->poolHasItem('itemA'))
            ->toBeFalse();

        $this->handler->writeSaveQueue();

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('writes immediately when it handles the command line and writes immediately', function (callable $pool) {

        $this->useCachePool($pool, cli: true);
        $this->handler->setHandleCli(true);
        $this->handler->setForceImmediateWrite(true);

        expect($this->handler->save('itemA', 'test'))
            ->toBeTrue()
            ->and($this->poolHasItem('itemA'))
            ->toBeTrue();
    });
})->with('cache pools');
