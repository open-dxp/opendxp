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

use DateTime;
use InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;

describe('the core cache handler', function () {
    it('is enabled unless something turns it off', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->handler->isEnabled())->toBeTrue();
    });

    it('answers a miss with false', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->handler->load('not_existing'))->toBeFalse();
    });

    it('answers a miss with an item that is no hit', function (callable $pool) {

        $this->useCachePool($pool);
        $item = $this->handler->getItem('not_existing');

        expect($item)
            ->toBeInstanceOf(CacheItem::class)
            ->and($item->isHit())
            ->toBeFalse();
    });

    it('hands back the object it was given, not a serialized copy', function (callable $pool) {

        $this->useCachePool($pool);
        $date = (new DateTime())->setTimestamp(time());

        $this->handler->save('date', $date);
        $this->handler->writeSaveQueue();
        $loaded = $this->handler->load('date');

        expect($loaded)
            ->toBeInstanceOf(DateTime::class)
            ->and($loaded->getTimestamp())
            ->toBe($date->getTimestamp());
    });
})->with('cache pools');

describe('an invalid item key', function () {
    it('is refused on save', function (callable $pool, string $key) {

        $this->useCachePool($pool);

        $this->handler->save($key, 'foo');
    })->throws(InvalidArgumentException::class);

    it('is refused on remove', function (callable $pool, string $key) {

        $this->useCachePool($pool);

        $this->handler->remove($key);
    })->throws(InvalidArgumentException::class);
})->with('cache pools')->with([
    '{str', 'rand{', 'rand{str', 'rand}str', 'rand(str',
    'rand)str', 'rand/str', 'rand\\str', 'rand@str', 'rand:str',
]);
