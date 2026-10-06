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

dataset('reserved keys', [
    'an opening brace' => ['item{a'],
    'a closing brace' => ['item}a'],
    'an opening parenthesis' => ['item(a'],
    'a closing parenthesis' => ['item)a'],
    'a slash' => ['item/a'],
    'a backslash' => ['item\\a'],
    'an at sign' => ['item@a'],
    'a colon' => ['item:a'],
]);

describe('the core cache handler', function () {
    it('returns false for a missing entry', function (callable $pool) {
        $this->useCachePool($pool);

        $loaded = $this->handler->load('itemA');

        expect($loaded)->toBeFalse();
    });

    it('returns an object it stored', function (callable $pool) {
        $this->useCachePool($pool);
        $date = new DateTime('2024-05-06 07:08:09');
        $this->handler->save('itemA', $date);
        $this->handler->writeSaveQueue();

        $loaded = $this->handler->load('itemA');

        expect($loaded)->toEqual($date);
    });
})->with('cache pools');

describe('a key with a reserved character', function () {
    it('is refused on save', function (callable $pool, string $key) {
        $this->useCachePool($pool);

        expect(fn () => $this->handler->save($key, 'test'))
            ->toThrow(InvalidArgumentException::class, 'contains reserved characters');
    });

    it('is refused on remove', function (callable $pool, string $key) {
        $this->useCachePool($pool);

        expect(fn () => $this->handler->remove($key))
            ->toThrow(InvalidArgumentException::class, 'contains reserved characters');
    });
})->with('cache pools')->with('reserved keys');
