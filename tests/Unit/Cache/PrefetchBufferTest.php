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

namespace OpenDxp\Tests\Unit\Cache;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The write bypasses the handler. A load that still returns the old value therefore read it from the buffer.
 */
function writeBehindHandler(TagAwareAdapterInterface $pool, string $key, string $value): void
{
    $item = $pool->getItem($key);
    $item->set($value);
    $pool->save($item);
}

beforeEach(function () {
    $this->pool = new TagAwareAdapter(new ArrayAdapter());
    $this->handler = cacheHandler($this->pool);
    $this->handler->setForceImmediateWrite(true);
});

it('serves a prefetched entry from the buffer', function () {
    $this->handler->save('bufferedKey', 'buffered-data', []);
    $this->handler->prefetch(['bufferedKey']);
    writeBehindHandler($this->pool, 'bufferedKey', 'fresh-data');

    $loaded = $this->handler->load('bufferedKey');

    expect($loaded)->toBe('buffered-data');
});

it('reads a key that was never prefetched from the pool', function () {
    $this->handler->save('plainKey', 'plain-data', []);
    $this->handler->prefetch(['someOtherKey']);

    $loaded = $this->handler->load('plainKey');

    expect($loaded)->toBe('plain-data');
});

it('lets a write overrule a buffered miss', function () {
    $this->handler->prefetch(['freshKey']);
    $this->handler->save('freshKey', 'fresh-data', []);

    $loaded = $this->handler->load('freshKey');

    expect($loaded)->toBe('fresh-data');
});

it('lets a removal overrule a buffered entry', function () {
    $this->handler->save('removedKey', 'stale-data', []);
    $this->handler->prefetch(['removedKey']);
    $this->handler->remove('removedKey');

    $loaded = $this->handler->load('removedKey');

    expect($loaded)->toBeFalse();
});

it('lets a cleared tag overrule a buffered entry', function () {
    $this->handler->save('taggedKey', 'tagged-data', ['some_tag']);
    $this->handler->prefetch(['taggedKey']);
    $this->handler->clearTags(['some_tag']);

    $loaded = $this->handler->load('taggedKey');

    expect($loaded)->toBeFalse();
});

it('serves a prefetched entry only once', function () {
    $this->handler->save('onceKey', 'buffered-data', []);
    $this->handler->prefetch(['onceKey']);
    $this->handler->load('onceKey');
    writeBehindHandler($this->pool, 'onceKey', 'fresh-data');

    $loaded = $this->handler->load('onceKey');

    expect($loaded)->toBe('fresh-data');
});

it('invalidates only the prefetched keys it is given', function () {
    $this->handler->save('invalidatedKey', 'buffered-data', []);
    $this->handler->save('keptKey', 'buffered-data', []);
    $this->handler->prefetch([
        'invalidatedKey',
        'keptKey',
    ]);
    writeBehindHandler($this->pool, 'invalidatedKey', 'fresh-data');
    writeBehindHandler($this->pool, 'keptKey', 'fresh-data');

    $this->handler->invalidatePrefetched(['invalidatedKey']);

    expect($this->handler->load('invalidatedKey'))
        ->toBe('fresh-data')
        ->and($this->handler->load('keptKey'))
        ->toBe('buffered-data');
});

it('drops the buffer when it is reset', function () {
    $this->handler->save('resetKey', 'buffered-data', []);
    $this->handler->prefetch(['resetKey']);
    writeBehindHandler($this->pool, 'resetKey', 'fresh-data');

    $this->handler->reset();

    expect($this->handler->load('resetKey'))->toBe('fresh-data');
});

// Symfony resets a service between two messenger messages only when it implements this interface.
it('can be reset between two messenger messages', function () {
    expect($this->handler)->toBeInstanceOf(ResetInterface::class);
});
