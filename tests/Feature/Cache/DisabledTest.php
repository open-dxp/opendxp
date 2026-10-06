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

describe('a disabled handler', function () {
    it('keeps the entries it held', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setForceImmediateWrite(true);
        $this->handler->save('itemA', 'test');

        $this->handler->disable();

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });

    it('refuses a new entry', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setForceImmediateWrite(true);
        $this->handler->disable();

        $saved = $this->handler->save('itemA', 'test');

        expect($saved)
            ->toBeFalse()
            ->and($this->poolHasItem('itemA'))
            ->toBeFalse();
    });
})->with('cache pools');
