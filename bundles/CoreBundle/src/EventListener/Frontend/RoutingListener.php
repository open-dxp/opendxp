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

use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Config;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use OpenDxp\Model\Site;
use OpenDxp\SystemSettingsConfig;
use OpenDxp\Tool;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Runs before dynamic routing kicks in and resolves site + handles redirects
 *
 * @internal
 */
class RoutingListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;
    use LoggerAwareTrait;

    public function __construct(
        protected RequestHelper $requestHelper,
        protected SiteResolver $siteResolver,
        protected Config $config
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run with high priority as we need to set the site early
            KernelEvents::REQUEST => ['onKernelRequest', 512],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // handle main domain redirect in admin context
        if ($this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)) {
            $this->handleMainDomainRedirect($event, true);

            return;
        }

        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $path = urldecode($request->getPathInfo());

        // resolve current site from request
        $this->resolveSite($request, $path);

        // check for app.php in URL and remove it for SEO puroposes
        $this->handleFrontControllerRedirect($event, $path);
        if ($event->hasResponse()) {
            return;
        }

        // redirect to the main domain if specified
        $this->handleMainDomainRedirect($event);
        if ($event->hasResponse()) {
        }
    }

    /**
     * Initialize Site
     *
     *
     */
    protected function resolveSite(Request $request, string $path): string
    {
        $site = null;

        // check for a registered site
        // do not initialize a site if it is a "special" admin request
        if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
            // host name without port incl. X-Forwarded-For handling for trusted proxies
            $host = $request->getHost();
            $site = Site::getByDomain($host);
        } elseif ($this->requestHelper->isObjectPreviewRequestByAdmin($request)) {
            // When rendering an object's preview tab, resolve the site via a parameter
            $siteId = $request->query->getInt(PreviewGeneratorInterface::PARAMETER_SITE);
            $site = Site::getById($siteId);
        }

        if ($site) {
            $path = $site->getRootPath() . $path;

            Site::setCurrentSite($site);

            $this->siteResolver->setSite($request, $site);
            $this->siteResolver->setSitePath($request, $path);
        }

        return $path;
    }

    protected function handleFrontControllerRedirect(RequestEvent $event, string $path): void
    {
        $request = $event->getRequest();

        // do not allow requests including /app.php/ => SEO
        // this is after the first redirect check, to allow redirects in app.php?xxx
        if (preg_match('@^/app\.php(.*)@', $path, $matches) && $request->getMethod() === 'GET') {
            $redirectUrl = $matches[1];
            $redirectUrl = ltrim($redirectUrl, '/');
            $redirectUrl = '/' . $redirectUrl;

            $event->setResponse(new RedirectResponse($redirectUrl, Response::HTTP_MOVED_PERMANENTLY));
        }
    }

    /**
     * Redirect to the main domain if specified
     *
     */
    protected function handleMainDomainRedirect(RequestEvent $event, bool $adminContext = false): void
    {
        $request = $event->getRequest();

        $hostRedirect = null;

        if ($adminContext) {
            $hostRedirect = $this->resolveConfigDomainRedirectHost($request);
        } elseif (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            if ($site->getRedirectToMainDomain() && $site->getMainDomain() !== $request->getHost()) {
                $hostRedirect = $site->getMainDomain();
            }
        } elseif (!$this->requestHelper->isFrontendRequestByAdmin()) {
            $hostRedirect = $this->resolveConfigDomainRedirectHost($request);
        }

        if ($hostRedirect && !$request->query->has('opendxp_disable_host_redirect')) {
            $qs = '';
            if (null !== $qs = $request->getQueryString()) {
                $qs = '?' . $qs;
            }

            $url = $request->getScheme() . '://' . $hostRedirect . $request->getBaseUrl() . $request->getPathInfo() . $qs;

            // log all redirects to the redirect log
            $this->logger->info(Tool::getAnonymizedClientIp(), ['Host-Redirect Source: ' . $request->getRequestUri() . ' -> ' . $url]);

            $event->setResponse(new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY));
        }
    }

    private function resolveConfigDomainRedirectHost(Request $request): ?string
    {
        $hostRedirect = null;

        $systemConfig = SystemSettingsConfig::get();
        $gc = $systemConfig['general'];
        if (isset($gc['redirect_to_maindomain']) && $gc['redirect_to_maindomain'] === true && isset($gc['domain']) && $gc['domain'] !== $request->getHost()) {
            return $gc['domain'];
        }

        return $hostRedirect;
    }
}
