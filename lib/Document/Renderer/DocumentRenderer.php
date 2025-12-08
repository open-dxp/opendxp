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

namespace OpenDxp\Document\Renderer;

use Exception;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use OpenDxp\Routing\Dynamic\DocumentRouteHandler;
use OpenDxp\Templating\Renderer\ActionRenderer;
use OpenDxp\Tool;
use OpenDxp\Tool\Frontend;
use OpenDxp\Twig\Extension\Templating\Placeholder\ContainerService;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

class DocumentRenderer implements DocumentRendererInterface
{
    public function __construct(private readonly RequestHelper $requestHelper, private readonly ActionRenderer $actionRenderer, private readonly FragmentRendererInterface $fragmentRenderer, private readonly DocumentRouteHandler $documentRouteHandler, private readonly EventDispatcherInterface $eventDispatcher, private readonly LocaleServiceInterface $localeService)
    {
    }

    #[Required]
    public function setContainerService(ContainerService $containerService): void
    {
        // we have to ensure that the ContainerService was initialized at the time this service is created
        // this is necessary, since the ContainerService registers a listener for DocumentEvents::RENDERER_PRE_RENDER
        // which wouldn't be called if the ContainerService would be lazy initialized when the first
        // placeholder service/templating helper is used during the rendering process
    }

    public function render(Document\PageSnippet $document, array $attributes = [], array $query = [], array $options = []): string
    {
        $isStaticPageGenerator = $attributes['opendxp_static_page_generator'] ?? false;
        $isCli = in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true);

        $this->eventDispatcher->dispatch(
            new DocumentEvent($document, $attributes),
            DocumentEvents::RENDERER_PRE_RENDER
        );

        // add document route to request if no route is set
        // this is needed for logic relying on the current route (e.g. OpenDxpUrl helper)
        if (!isset($attributes['_route'])) {
            $route = $this->documentRouteHandler->buildRouteForDocument($document);
            $attributes['_route'] = $route?->getRouteKey();
        }

        try {
            $request = $this->requestHelper->getCurrentRequest();
        } catch (Exception) {

            $host = null;
            $url = $document->getFullPath();
            if ($site = Frontend::getSiteForDocument($document)) {
                Site::setCurrentSite($site);
                $host = $site->getMainDomain();
                $url = preg_replace('@^' . $site->getRootPath() . '/?@', '/', $url);
            } elseif ($systemMainDomain = Tool::getHostname()) {
                $host = $systemMainDomain;
            }

            $request = $this->requestHelper->createRequestWithContext(uri: $url, host: $host);
        }

        if ($isStaticPageGenerator) {
            $headers = \OpenDxp\Config::getSystemConfiguration('documents')['static_page_generator']['headers'];
            foreach ($headers as $header) {
                $request->headers->set($header['name'], $header['value']);
            }
        }

        $documentLocale = $document->getProperty('language');
        $tempLocale = $this->localeService->getLocale();
        if ($documentLocale) {
            $this->localeService->setLocale($documentLocale);
            $request->setLocale($documentLocale);
        }

        if ($isStaticPageGenerator && $isCli && !$this->requestHelper->hasMainRequest()) {
            $this->requestHelper->pushRequest($request);
        }

        $uri = $this->actionRenderer->createDocumentReference($document, $attributes, $query);
        $response = $this->fragmentRenderer->render($uri, $request, $options);

        $this->localeService->setLocale($tempLocale);

        $this->eventDispatcher->dispatch(
            new DocumentEvent($document, $attributes),
            DocumentEvents::RENDERER_POST_RENDER
        );

        try {
            return $response->getContent();
        } finally {
            if ($isStaticPageGenerator && $isCli) {
                while ($this->requestHelper->hasCurrentRequest()) {
                    $this->requestHelper->popRequest();
                }
            }
        }
    }
}
