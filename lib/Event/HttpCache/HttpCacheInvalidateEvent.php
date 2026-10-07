<?php

declare(strict_types=1);

namespace OpenDxp\Event\HttpCache;

use OpenDxp\HttpCache\Tag\CacheTag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @see \OpenDxp\Event\HttpCacheEvents::INVALIDATE
 */
final class HttpCacheInvalidateEvent extends Event
{
    /**
     * @param list<CacheTag> $tags
     */
    public function __construct(
        private readonly array $tags,
        public readonly object $element,
    ) {
    }

    /**
     * @return list<CacheTag>
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
