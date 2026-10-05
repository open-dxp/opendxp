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

describe('the write lock', function () {
    it('is taken when an entry is removed', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->lock->hasLock())->toBeFalse();

        $this->handler->remove('foo');

        expect($this->lock->hasLock())->toBeTrue();
    });

    it('is taken when a tag is cleared', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->lock->hasLock())->toBeFalse();

        $this->handler->clearTag('foo');

        expect($this->lock->hasLock())->toBeTrue();
    });

    it('is taken when several tags are cleared', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->lock->hasLock())->toBeFalse();

        $this->handler->clearTags(['foo']);

        expect($this->lock->hasLock())->toBeTrue();
    });

    it('is taken when everything is cleared', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->lock->hasLock())->toBeFalse();

        $this->handler->clearAll();

        expect($this->lock->hasLock())->toBeTrue();
    });

    it('is taken when a tag is held back for the shutdown', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->lock->hasLock())->toBeFalse();

        $this->handler->addTagClearedOnShutdown('foo');

        expect($this->lock->hasLock())->toBeTrue();
    });

    it('is given up on shutdown', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->clearAll();

        expect($this->lock->hasLock())->toBeTrue();

        $this->handler->shutdown();

        expect($this->lock->hasLock())->toBeFalse();
    });

    it('does not stop an entry the caller forces', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test', [], null, null, true);

        expect($this->poolHasItem('itemA'))->toBeTrue();

        $this->lock->lock();
        $this->lock->disable();

        $this->handler->save('itemB', 'test', [], null, null, true);

        expect($this->poolHasItem('itemB'))->toBeTrue();
    });
})->with('cache pools');
