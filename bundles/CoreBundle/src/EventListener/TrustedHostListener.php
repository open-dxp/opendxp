<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CoreBundle\EventListener;

use OpenDxp\Http\Request\Host\TrustedHosts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final class TrustedHostListener implements EventSubscriberInterface
{
    private ?bool $setsTrustedHosts = null;

    public function __construct(private readonly TrustedHosts $trustedHosts)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // The routing listener reads the host at priority 512.
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // A project with framework.trusted_hosts or TRUSTED_HOSTS checks the hosts with its own patterns.
        $this->setsTrustedHosts ??= Request::getTrustedHosts() === [];

        if ($this->setsTrustedHosts) {
            Request::setTrustedHosts($this->trustedHosts->getPatterns());
        }
    }
}
