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

describe('a handler on the command line', function () {
    it('refuses an entry', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setHandleCli(false);

        $saved = $this->handler->save('itemA', 'test');

        expect($saved)->toBeFalse();
    });

    it('refuses an entry even when it writes at once', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setHandleCli(false);
        $this->handler->setForceImmediateWrite(true);

        $saved = $this->handler->save('itemA', 'test');

        expect($saved)
            ->toBeFalse()
            ->and($this->poolHasItem('itemA'))
            ->toBeFalse();
    });

    it('writes an entry the caller forces', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setHandleCli(false);

        $saved = $this->handler->save('itemA', 'test', force: true);

        expect($saved)
            ->toBeTrue()
            ->and($this->poolHasItem('itemA'))
            ->toBeTrue();
    });
})->with('cache pools');
