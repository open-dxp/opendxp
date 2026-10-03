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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHitCounter;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RoutingListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public function __construct(
        protected RedirectHandler $redirectHandler,
        protected ?RedirectHitCounter $hitCounter = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                // a domain redirect runs before the core redirects a host to its main domain
                ['onKernelRequestForDomain', 520],
                // run with high priority as we need to set the site early
                ['onKernelRequest', 256],
            ],

            // run with high priority before handling real errors
            KernelEvents::EXCEPTION => ['onKernelException', 64],
        ];
    }

    public function onKernelRequestForDomain(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $response = $this->redirectHandler->checkForDomainRedirect($request);
        if ($response) {
            $event->setResponse($response);
            $this->hitCounter?->count($response);
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $response = $this->redirectHandler->checkForRedirect($request, true);
        if ($response) {
            $event->setResponse($response);
            $this->hitCounter?->count($response);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        // in case routing didn't find a matching route, check for redirects without override
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException) {
            $response = $this->redirectHandler->checkForRedirect($request, false);
            if ($response) {
                $event->setResponse($response);
                $this->hitCounter?->count($response);
            }
        }
    }
}
