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

use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Service\ResetInterface;

beforeEach(function () {
    $this->pool = new TagAwareAdapter(new ArrayAdapter());

    $lock = new WriteLock($this->pool);
    $lock->setLogger(new NullLogger());

    $this->handler = new CoreCacheHandler($this->pool, $lock, new EventDispatcher());
    $this->handler->setLogger(new NullLogger());
    $this->handler->setHandleCli(true);
    $this->handler->setForceImmediateWrite(true);
});

it('serves a prefetched entry from the buffer', function () {

    $this->handler->save('hitKey', 'hit-data', []);
    $this->handler->prefetch(['hitKey', 'missKey']);

    expect($this->handler->load('hitKey'))
        ->toBe('hit-data')
        ->and($this->handler->load('missKey'))
        ->toBeFalse();
});

it('reads a key that was never prefetched from the pool', function () {

    $this->handler->save('plainKey', 'plain-data', []);
    $this->handler->prefetch(['someOtherKey']);

    expect($this->handler->load('plainKey'))->toBe('plain-data');
});

it('lets a write overrule a buffered miss', function () {

    $this->handler->prefetch(['freshKey']);
    $this->handler->save('freshKey', 'fresh-data', []);

    expect($this->handler->load('freshKey'))->toBe('fresh-data');
});

it('lets a removal overrule a buffered entry', function () {

    $this->handler->save('removedKey', 'stale-data', []);
    $this->handler->prefetch(['removedKey']);
    $this->handler->remove('removedKey');

    expect($this->handler->load('removedKey'))->toBeFalse();
});

it('lets a cleared tag overrule a buffered entry', function () {

    $this->handler->save('taggedKey', 'tagged-data', ['some_tag']);
    $this->handler->prefetch(['taggedKey']);
    $this->handler->clearTags(['some_tag']);

    expect($this->handler->load('taggedKey'))->toBeFalse();
});

it('hands a prefetched entry out once and reads the pool afterwards', function () {

    $this->handler->save('onceKey', 'first-value', []);
    $this->handler->prefetch(['onceKey']);

    expect($this->handler->load('onceKey'))->toBe('first-value');

    $this->handler->save('onceKey', 'second-value', []);

    expect($this->handler->load('onceKey'))->toBe('second-value');
});

it('invalidates only the keys it is given', function () {

    $this->handler->save('batchKey', 'batch-data', []);
    $this->handler->save('otherBatchKey', 'other-batch-data', []);
    $this->handler->prefetch(['batchKey', 'otherBatchKey']);

    // Written past the handler, so a load can only answer with the buffered value.
    foreach (['batchKey' => 'fresh-batch-data', 'otherBatchKey' => 'fresh-other-batch-data'] as $key => $value) {
        $item = $this->pool->getItem($key);
        $item->set($value);
        $this->pool->save($item);
    }

    $this->handler->invalidatePrefetched(['batchKey']);

    expect($this->handler->load('batchKey'))
        ->toBe('fresh-batch-data')
        ->and($this->handler->load('otherBatchKey'))
        ->toBe('other-batch-data');
});

it('drops the buffer when it is reset', function () {

    $this->handler->save('resetKey', 'stale-data', []);
    $this->handler->prefetch(['resetKey']);

    $item = $this->pool->getItem('resetKey');
    $item->set('fresh-data');
    $this->pool->save($item);

    $this->handler->reset();

    expect($this->handler->load('resetKey'))->toBe('fresh-data');
});

// kernel.reset picks the handler up through autoconfiguration, so a messenger
// worker only gets a clean buffer as long as this interface is implemented.
it('can be reset between messenger messages', function () {

    expect($this->handler)->toBeInstanceOf(ResetInterface::class);
});
