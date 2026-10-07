<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\HttpCache;

use ArrayObject;
use OpenDxp\Event\HttpCache\HttpCacheInvalidateEvent;
use OpenDxp\Event\HttpCacheEvents;
use OpenDxp\HttpCache\HttpCacheArguments;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @return ArrayObject<int, string>
 */
function invalidatedTags(): ArrayObject
{
    $tags = new ArrayObject();
    Container::get(EventDispatcherInterface::class)->addListener(
        HttpCacheEvents::INVALIDATE,
        static function (HttpCacheInvalidateEvent $event) use ($tags): void {
            foreach ($event->getTags() as $tag) {
                $tags->append((string) $tag);
            }
        },
    );

    return $tags;
}

it('tells the listeners which tags a change invalidates', function () {
    $page = DocumentPageFactory::createOne();
    $tags = invalidatedTags();

    $page->save();

    expect($tags->getArrayCopy())->toContain(sprintf('document_%d', $page->getId()));
});

it('tells the listeners nothing when a save skips the invalidation', function () {
    $page = DocumentPageFactory::createOne();
    $tags = invalidatedTags();

    $page->save([HttpCacheArguments::SKIP_INVALIDATION => true]);

    expect($tags->getArrayCopy())->toBe([]);
});
