<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Answers every request to the host of a domain redirect.
 *
 * It runs before the core redirects an additional domain to the main domain of its site, and it is a listener of its
 * own, so a bundle that replaces the routing listener of the SEO bundle keeps domain redirects working.
 *
 * @internal
 */
final class DomainRedirectListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public function __construct(private readonly RedirectHandler $redirectHandler)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 520],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $response = $this->redirectHandler->checkForDomainRedirect($request);
        if ($response) {
            $event->setResponse($response);
        }
    }
}
