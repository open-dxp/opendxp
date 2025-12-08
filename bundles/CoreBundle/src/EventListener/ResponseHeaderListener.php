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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\CoreBundle\EventListener;

use OpenDxp\Controller\Attribute\ResponseHeader;
use OpenDxp\Http\Request\Resolver\ResponseHeaderResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class ResponseHeaderListener implements EventSubscriberInterface
{
    public function __construct(private readonly ResponseHeaderResolver $responseHeaderResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', 32],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $headers = $this->responseHeaderResolver->getResponseHeaders($event->getRequest());
        $response = $event->getResponse();

        foreach ($headers as $header) {
            $response->headers->set($header->getKey(), $header->getValues(), $header->getReplace());
        }
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        if (!is_array($attributes = $event->getAttributes()[ResponseHeader::class] ?? null)) {
            return;
        }

        $responseHeaders = [...$this->responseHeaderResolver->getResponseHeaders($request), ...$attributes];

        $request->attributes->set($this->responseHeaderResolver::ATTRIBUTE_RESPONSE_HEADER, $responseHeaders);
    }
}
