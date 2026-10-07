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

namespace OpenDxp\HttpCache;

use FOS\HttpCacheBundle\CacheManager;
use OpenDxp\Event\HttpCache\HttpCacheInvalidateEvent;
use OpenDxp\Event\HttpCache\HttpCacheTagGuardEvent;
use OpenDxp\Event\HttpCacheEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HttpCache
{
    /**
     * @param iterable<HttpCacheTagStrategyInterface> $strategies
     */
    public function __construct(
        private readonly iterable $strategies,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly HttpCacheTagCollectorInterface $collector,
        private readonly HttpCacheScope $scope,
        private readonly ?CacheManager $cacheManager = null,
    ) {
    }

    /**
     * Adds cache tags for the given element to the current response
     */
    public function collectTagsFor(object $element): void
    {
        if (!$this->scope->isActive()) {
            return;
        }

        $tags = [];
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($element)) {
                array_push($tags, ...$strategy->getTags($element));
            }
        }

        if ($tags === []) {
            return;
        }

        $event = new HttpCacheTagGuardEvent($tags, $element);
        $this->dispatcher->dispatch($event, HttpCacheEvents::TAG_GUARD);

        if (!$event->isCancelled()) {
            $this->collector->addTag(...$tags);
        }
    }

    /**
     * Invalidates cache entries tagged for the given element via all matching strategies
     */
    public function invalidate(object $element): void
    {
        $tags = [];
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($element)) {
                array_push($tags, ...$strategy->getTags($element));
            }
        }

        if ($tags === []) {
            return;
        }

        $this->dispatcher->dispatch(new HttpCacheInvalidateEvent($tags, $element), HttpCacheEvents::INVALIDATE);
        $this->cacheManager?->invalidateTags(array_map(strval(...), $tags));
    }
}
