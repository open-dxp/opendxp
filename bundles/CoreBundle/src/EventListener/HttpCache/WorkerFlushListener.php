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

namespace OpenDxp\Bundle\CoreBundle\EventListener\HttpCache;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCacheBundle\CacheManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

final readonly class WorkerFlushListener implements EventSubscriberInterface
{
    public function __construct(private ?CacheManager $cacheManager = null)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageHandledEvent::class => 'flush',
            WorkerMessageFailedEvent::class => 'flush',
        ];
    }

    public function flush(): void
    {
        try {
            $this->cacheManager?->flush();
        } catch (ExceptionCollection) {
            // FOSHttpCacheBundle logs the failures through its own log listener, as it does at the end of a request.
        }
    }
}
