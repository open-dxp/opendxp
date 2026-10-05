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

describe('a disabled cache', function () {
    it('keeps what was written before and takes nothing new', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->setForceImmediateWrite(true);
        $this->handler->save('item_before', 'test', ['before', 'generic']);

        $this->handler->disable();

        expect($this->handler->isEnabled())
            ->toBeFalse()
            ->and($this->poolHasItem('item_before'))
            ->toBeTrue()
            ->and($this->handler->save('item_after', 'test', ['after', 'generic']))
            ->toBeFalse()
            ->and($this->poolHasItem('item_after'))
            ->toBeFalse();
    });
})->with('cache pools');
