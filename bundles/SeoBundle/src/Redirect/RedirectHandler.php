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

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use Exception;
use Generator;
use OpenDxp\Bundle\SeoBundle\Event\Model\RedirectEvent;
use OpenDxp\Bundle\SeoBundle\Event\RedirectEvents;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Helper\StringHelper;
use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use OpenDxp\Tool;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final class RedirectHandler
{
    use RecursionBlockingEventDispatchHelperTrait;

    public const string RESPONSE_HEADER_NAME_ID = 'X-OpenDxp-Redirect-ID';

    public function __construct(
        private RequestHelper $requestHelper,
        private SiteResolver $siteResolver,
        private RedirectCache $redirectCache,
        private LoggerInterface $logger,
        private LoggerInterface $redirectLogger,
        private GeneralHostResolver $generalHostResolver,
    ) {
    }

    /**
     * @throws Exception
     */
    public function checkForRedirect(Request $request, bool $override = false, ?Site $sourceSite = null): ?Response
    {
        // not for admin requests
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return null;
        }

        $cachedRedirects = $this->redirectCache->get();
        if (!$cachedRedirects->installed) {
            return null;
        }

        // get current site if available
        if (!$sourceSite && $this->siteResolver->isSiteRequest($request)) {
            $sourceSite = $this->siteResolver->getSite($request);
        }

        $partResolver = new RedirectUrlPartResolver($request);
        $now = time();

        $redirects = $this->matchingRedirects($request, $override, $sourceSite, $cachedRedirects, $partResolver, $now);
        foreach ($redirects as [$redirect, $matches]) {
            // A redirect with priority 99 does not take a request away from a protected redirect.
            if ($override && !$redirect->isProtected()
                && $this->matchesProtectedRedirect($request, $sourceSite, $cachedRedirects, $partResolver, $now)
            ) {
                return null;
            }

            $response = $this->buildRedirectResponse($redirect, $request, $matches);
            if ($response instanceof Response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * A domain redirect answers every request to its host, whatever its path.
     *
     * @throws Exception
     */
    public function checkForDomainRedirect(Request $request): ?Response
    {
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return null;
        }

        $now = time();

        foreach ($this->redirectCache->get()->domainRedirects[$request->getHost()] ?? [] as $redirect) {
            if ($this->isInEffect($redirect, $now)) {
                return $this->buildRedirectResponse($redirect, $request);
            }
        }

        return null;
    }

    /**
     * Yields the redirects that match the request, each with the matches of its regular expression:
     * - protected redirects before the others
     * - an exact source before a regular expression
     *
     * @return Generator<array{Redirect, array<int|string, string>}>
     */
    private function matchingRedirects(
        Request $request,
        bool $override,
        ?Site $sourceSite,
        CachedRedirects $cachedRedirects,
        RedirectUrlPartResolver $partResolver,
        int $now,
    ): Generator {
        // Before routing, only a source with priority 99 may redirect. The database is asked only when one exists.
        $exactMatch = !$override || $cachedRedirects->hasOverridingSources
            ? Redirect::getByExactMatch($request, $sourceSite, $override)
            : null;

        foreach ([true, false] as $protected) {
            if ($exactMatch !== null && $exactMatch->isProtected() === $protected) {
                yield [$exactMatch, []];
            }

            foreach ($cachedRedirects->regularExpressions as $redirect) {
                if ($redirect->isProtected() !== $protected || ($redirect->getPriority() === 99) !== $override) {
                    continue;
                }

                $matches = $this->matchRegularExpression($redirect, $partResolver, $sourceSite, $now);
                if ($matches !== null) {
                    yield [$redirect, $matches];
                }
            }
        }
    }

    private function matchesProtectedRedirect(
        Request $request,
        ?Site $sourceSite,
        CachedRedirects $cachedRedirects,
        RedirectUrlPartResolver $partResolver,
        int $now,
    ): bool {
        if (Redirect::getByExactMatch($request, $sourceSite)?->isProtected()) {
            return true;
        }

        foreach ($cachedRedirects->regularExpressions as $redirect) {
            if ($redirect->isProtected()
                && $this->matchRegularExpression($redirect, $partResolver, $sourceSite, $now) !== null
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the matches of the regular expression, or null when the redirect does not apply to the request.
     *
     * @return array<int|string, string>|null
     */
    private function matchRegularExpression(
        Redirect $redirect,
        RedirectUrlPartResolver $partResolver,
        ?Site $sourceSite,
        int $now,
    ): ?array {
        if (!$this->isInEffect($redirect, $now) || $redirect->getSourceSite() !== $sourceSite?->getId()) {
            return null;
        }

        $matches = [];
        $part = $partResolver->getRequestUriPart($redirect->getType());

        return preg_match((string) $redirect->getSource(), $part, $matches) === 1 ? $matches : null;
    }

    /**
     * Returns whether the redirect applies at the time. The database applies the same rule to exact sources.
     */
    private function isInEffect(Redirect $redirect, int $now): bool
    {
        return ($redirect->getValidFrom() === null || $redirect->getValidFrom() <= $now)
            && ($redirect->getExpiry() === null || $redirect->getExpiry() > $now);
    }

    /**
     * @throws Exception
     */
    protected function buildRedirectResponse(Redirect $redirect, Request $request, array $matches = []): ?Response
    {
        $this->dispatchEvent(new RedirectEvent($redirect), RedirectEvents::PRE_BUILD);
        $target = $redirect->getTarget();
        if (is_numeric($target)) {
            $d = Document::getById((int) $target);
            if ($d instanceof Document\Page || $d instanceof Document\Link || $d instanceof Document\Hardlink) {
                $target = $d->getFullPath();
            } else {
                $this->logger->error('Target of redirect {redirect} not found (Document-ID: {document})', [
                    'redirect' => $redirect->getId(),
                    'document' => $target,
                ]);

                return null;
            }
        }

        $url = $target;
        if ($redirect->isRegex()) {
            array_shift($matches);

            // support for pcre backreferences
            $url = StringHelper::replacePcreBackreferences($url, $matches);
        }

        if (!preg_match('@http(s)?://@i', $url)) {
            if ($redirect->getTargetSite()) {
                if ($targetSite = Site::getById($redirect->getTargetSite())) {
                    // if the target site is specified and and the target-path is starting at root (not absolute to site)
                    // the root-path will be replaced so that the page can be shown
                    $url = preg_replace('@^' . $targetSite->getRootPath() . '/@', '/', $url);
                    $url = $request->getScheme() . '://' . $targetSite->getMainDomain() . $url;
                } else {
                    $this->logger->error('Site with ID {targetSite} not found', [
                        'redirect' => $redirect->getId(),
                        'targetSite' => $redirect->getTargetSite(),
                    ]);

                    return null;
                }
            } else {
                $site = Site::getByDomain($request->getHost());
                $redirectDomain = $site instanceof Site ? $request->getHost() : $this->generalHostResolver->resolve([GeneralHostProviderInterface::CONTEXT_SOURCE => $request]);

                if ($redirectDomain) {
                    // prepend the host and scheme to avoid infinite loops when using "domain" redirects
                    $url = $request->getScheme().'://'.$redirectDomain.$url;
                }
            }
        }

        if ($redirect->getType() === Redirect::TYPE_DOMAIN && $redirect->getPassThroughPath()) {
            [$path, $query] = array_pad(explode('?', $url, 2), 2, null);
            $url = rtrim($path, '/') . $request->getPathInfo() . ($query !== null ? '?' . $query : '');
        }

        // pass-through parameters if specified
        $queryString = $request->getQueryString();
        if ($redirect->getPassThroughParameters() && !empty($queryString)) {
            $glue = '?';
            if (strpos($url, '?')) {
                $glue = '&';
            }

            $url .= $glue;
            $url .= $queryString;
        }

        $statusCode = $redirect->getStatusCode() ?: Response::HTTP_MOVED_PERMANENTLY;
        $response = new Response(null, $statusCode);

        if ($response->isRedirect()) {
            $response = new RedirectResponse($url, $statusCode);
        }

        $response->headers->set(self::RESPONSE_HEADER_NAME_ID, (string) $redirect->getId());

        $this->redirectLogger->info(Tool::getAnonymizedClientIp() ?? 'Anonymous', ['Custom-Redirect ID: ' . $redirect->getId() . ', Source: ' . $request->getRequestUri() . ' -> ' . $url]);

        return $response;
    }
}
