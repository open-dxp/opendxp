<?php

declare(strict_types=1);

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
