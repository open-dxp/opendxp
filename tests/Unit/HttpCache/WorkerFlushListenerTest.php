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

namespace OpenDxp\Tests\Unit\HttpCache;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCacheBundle\CacheManager;
use OpenDxp\Bundle\CoreBundle\EventListener\HttpCache\WorkerFlushListener;
use RuntimeException;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

beforeEach(function () {
    $this->cacheManager = $this->createMock(CacheManager::class);
    $this->dispatcher = new EventDispatcher();
    $this->dispatcher->addSubscriber(new WorkerFlushListener($this->cacheManager));
});

it('sends the invalidations of a message once the worker is done with it', function (object $event) {
    $this->cacheManager
        ->expects($this->once())
        ->method('flush');

    $this->dispatcher->dispatch($event);
})->with([
    'a handled message' => [
        fn () => new WorkerMessageHandledEvent(new Envelope(new stdClass()), 'async'),
    ],
    'a failed message' => [
        fn () => new WorkerMessageFailedEvent(new Envelope(new stdClass()), 'async', new RuntimeException()),
    ],
]);

it('keeps the worker running when the proxy cannot be reached', function () {
    $this->cacheManager
        ->method('flush')
        ->willThrowException(new ExceptionCollection());

    $event = new WorkerMessageHandledEvent(new Envelope(new stdClass()), 'async');

    expect(fn () => $this->dispatcher->dispatch($event))->not->toThrow(ExceptionCollection::class);
});
