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

use OpenDxp\Cache\Core\CoreCacheHandler;

it('takes the write lock on a change to the cache', function (callable $pool, callable $change) {
    $this->useCachePool($pool);

    $change($this->handler);

    expect($this->lock->hasLock())->toBeTrue();
})->with('cache pools')->with([
    'removing an entry' => [
        fn (CoreCacheHandler $handler) => $handler->remove('foo'),
    ],
    'clearing a tag' => [
        fn (CoreCacheHandler $handler) => $handler->clearTag('foo'),
    ],
    'clearing several tags' => [
        fn (CoreCacheHandler $handler) => $handler->clearTags(['foo']),
    ],
    'clearing everything' => [
        fn (CoreCacheHandler $handler) => $handler->clearAll(),
    ],
    'holding a tag for the shutdown' => [
        fn (CoreCacheHandler $handler) => $handler->addTagClearedOnShutdown('foo'),
    ],
]);

describe('the write lock', function () {
    it('is given up on shutdown', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->clearAll();

        $this->handler->shutdown();

        expect($this->lock->hasLock())->toBeFalse();
    });

    it('holds the save queue back', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->save('itemA', 'test');
        $this->lock->lock();

        $this->handler->writeSaveQueue();

        expect($this->poolHasItem('itemA'))->toBeFalse();
    });

    it('lets an entry through that the caller forces', function (callable $pool) {
        $this->useCachePool($pool);
        $this->lock->lock();

        $this->handler->save('itemA', 'test', force: true);

        expect($this->poolHasItem('itemA'))->toBeTrue();
    });
})->with('cache pools');
