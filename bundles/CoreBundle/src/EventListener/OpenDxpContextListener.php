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

use OpenDxp;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class OpenDxpContextListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const ATTRIBUTE_OPENDXP_CONTEXT_FORCE_RESOLVING = '_opendxp_context_force_resolving';

    public function __construct(
        protected OpenDxpContextResolver $resolver,
        protected RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run after router to be able to match the _route attribute
            // TODO check if this is early enough
            KernelEvents::REQUEST => ['onKernelRequest', 24],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($event->isMainRequest() || $event->getRequest()->attributes->has(self::ATTRIBUTE_OPENDXP_CONTEXT_FORCE_RESOLVING)) {
            $context = $this->resolver->getOpenDxpContext($request);

            if ($context) {
                $this->logger->debug('Resolved opendxp context for path {path} to {context}', [
                    'path' => $request->getPathInfo(),
                    'context' => $context,
                ]);
            } else {
                $this->logger->debug('Could not resolve a opendxp context for path {path}', [
                    'path' => $request->getPathInfo(),
                ]);
            }

            $this->initializeContext($context, $request);
        }
    }

    /**
     * Do context specific initialization
     *
     */
    protected function initializeContext(string $context, Request $request): void
    {
        if ($context === OpenDxpContextResolver::CONTEXT_ADMIN) {
            OpenDxp::setAdminMode();
            Document::setHideUnpublished(false);
            DataObject::setHideUnpublished(false);
            DataObject\Localizedfield::setGetFallbackValues(false);
        } else {
            OpenDxp::unsetAdminMode();
            Document::setHideUnpublished(true);
            DataObject::setHideUnpublished(true);
            DataObject::setGetInheritedValues(true);
            DataObject\Localizedfield::setGetFallbackValues(true);
        }
    }
}
