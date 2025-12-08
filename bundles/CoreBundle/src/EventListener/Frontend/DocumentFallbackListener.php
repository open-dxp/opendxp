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

namespace OpenDxp\Bundle\CoreBundle\EventListener\Frontend;

use OpenDxp\Bundle\CoreBundle\Controller\PublicServicesController;
use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Model\Document;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * If no document was found on the active request (not set by router or by initiator of a sub-request), try to find and
 * set a fallback document:
 *
 *  - if request is a sub-request, try to read document from main request
 *  - if all fails, try to find the nearest document by path
 *
 * @internal
 */
class DocumentFallbackListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    protected array $options;

    private ?Document $fallbackDocument = null;

    public function __construct(
        protected RequestStack $requestStack,
        protected DocumentResolver $documentResolver,
        protected SiteResolver $siteResolver,
        protected Document\Service $documentService,
        array $options = []
    ) {
        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'nearestDocumentTypes' => ['page', 'snippet', 'hardlink', 'link', 'folder'],
        ]);

        $optionsResolver->setAllowedTypes('nearestDocumentTypes', 'array');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // priority must be before
            // -> Symfony\Component\HttpKernel\EventListener\LocaleListener::onKernelRequest()
            // -> OpenDxp\Bundle\CoreBundle\EventListener\Frontend\EditmodeListener::onKernelRequest()
            KernelEvents::REQUEST => ['onKernelRequest', 20],
            KernelEvents::CONTROLLER => ['onKernelController', 50],
        ];
    }

    /**
     * Finds the nearest document for the current request if the routing/document router didn't find one (e.g. static routes)
     *
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($this->documentResolver->getDocument($request)) {
            return;
        }

        if ($event->isMainRequest()) {
            // no document found yet - try to find the nearest document by request path
            // this is only done on the main request as a sub-request's pathInfo is _fragment when
            // rendered via actions helper
            $path = null;
            if ($this->siteResolver->isSiteRequest($request)) {
                $path = $this->siteResolver->getSitePath($request);
            } else {
                $path = urldecode($request->getPathInfo());
            }

            $document = $this->documentService->getNearestDocumentByPath($path, false, $this->options['nearestDocumentTypes']);
            if ($document) {
                $this->fallbackDocument = $document;
                if ($document->getProperty('language')) {
                    $request->setLocale($document->getProperty('language'));
                }
            }
        } else {
            // if we're in a sub request and no explicit document is set - try to load document from
            // parent and/or main request and set it on our sub-request
            $parentRequest = $this->requestStack->getParentRequest();
            $mainRequest = $this->requestStack->getMainRequest();

            $eligibleRequests = [];

            if ($parentRequest instanceof \Symfony\Component\HttpFoundation\Request) {
                $eligibleRequests[] = $parentRequest;
            }

            if ($mainRequest !== $parentRequest) {
                $eligibleRequests[] = $mainRequest;
            }

            foreach ($eligibleRequests as $eligibleRequest) {
                if ($document = $this->documentResolver->getDocument($eligibleRequest)) {
                    $this->documentResolver->setDocument($request, $document);

                    return;
                }
            }
        }
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (is_array($controller) && isset($controller[0]) && $controller[0] instanceof PublicServicesController) {
            // ignore PublicServicesController because this could lead to conflicts of Asset and Document paths, see #2704
            return;
        }

        if ($this->fallbackDocument && $event->isMainRequest()) {
            $this->documentResolver->setDocument($event->getRequest(), $this->fallbackDocument);
            $this->fallbackDocument = null;
        }
    }
}
