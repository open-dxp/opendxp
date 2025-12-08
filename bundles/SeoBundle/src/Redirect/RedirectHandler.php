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

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use Exception;
use OpenDxp\Bundle\SeoBundle\Event\Model\RedirectEvent;
use OpenDxp\Bundle\SeoBundle\Event\RedirectEvents;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Cache;
use OpenDxp\Config;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use OpenDxp\Tool;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 */
final class RedirectHandler
{
    use RecursionBlockingEventDispatchHelperTrait;

    const string RESPONSE_HEADER_NAME_ID = 'X-OpenDxp-Redirect-ID';

    /**
     * @var Redirect[]|null
     */
    private ?array $redirects = null;

    private ?LockInterface $lock = null;

    public function __construct(private RequestHelper $requestHelper, private SiteResolver $siteResolver, private Config $config, LockFactory $lockFactory, private LoggerInterface $logger, private LoggerInterface $redirectLogger)
    {
        $this->lock = $lockFactory->createLock(self::class);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function checkForRedirect(Request $request, bool $override = false, ?Site $sourceSite = null): ?Response
    {
        // not for admin requests
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return null;
        }

        // get current site if available
        if (!$sourceSite && $this->siteResolver->isSiteRequest($request)) {
            $sourceSite = $this->siteResolver->getSite($request);
        }

        if ((($redirect = Redirect::getByExactMatch($request, $sourceSite, $override))) && ($response = $this->buildRedirectResponse($redirect, $request)) instanceof \Symfony\Component\HttpFoundation\Response) {
            return $response;
        }

        $partResolver = new RedirectUrlPartResolver($request);
        foreach ($this->getRegexFilteredRedirects($override) as $redirect) {
            if (($response = $this->matchRegexRedirect($redirect, $request, $partResolver, $sourceSite)) instanceof \Symfony\Component\HttpFoundation\Response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function matchRegexRedirect(
        Redirect $redirect,
        Request $request,
        RedirectUrlPartResolver $partResolver,
        ?Site $sourceSite = null
    ): ?Response {
        if (empty($redirect->getType())) {
            return null;
        }

        $matchPart = $partResolver->getRequestUriPart($redirect->getType());
        $matches = [];

        $doesMatch = false;
        if ($redirect->isRegex()) {
            $doesMatch = (bool)@preg_match($redirect->getSource(), $matchPart, $matches);
        } else {
            $source = str_replace('+', ' ', $redirect->getSource()); // see #2202
            $doesMatch = $source === $matchPart;
        }

        if (!$doesMatch) {
            return null;
        }

        // check for a site
        if (($redirect->getSourceSite() || $sourceSite) && (!$sourceSite || $sourceSite->getId() !== $redirect->getSourceSite())) {
            return null;
        }

        return $this->buildRedirectResponse($redirect, $request, $matches);
    }

    /**
     *
     *
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
            $url = replace_pcre_backreferences($url, $matches);
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
                $redirectDomain = $site instanceof Site ? $request->getHost() : $this->config['general']['domain'];

                if ($redirectDomain) {
                    // prepend the host and scheme to avoid infinite loops when using "domain" redirects
                    $url = $request->getScheme().'://'.$redirectDomain.$url;
                }
            }
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

    /**
     * @return Redirect[]
     */
    private function getRegexRedirects(): array
    {
        if (is_array($this->redirects)) {
            return $this->redirects;
        }

        $cacheKey = 'system_route_redirect';
        $valueFromCache = Cache::load($cacheKey);
        $this->redirects = $valueFromCache === false ? null : $valueFromCache;
        if ($this->redirects === null) {
            // acquire lock to avoid concurrent redirect cache warm-up
            $this->lock->acquire(true);

            //check again if redirects are cached to avoid re-warming cache
            $valueFromCache = Cache::load($cacheKey);
            $this->redirects = $valueFromCache === false ? null : $valueFromCache;
            if ($this->redirects === null) {
                try {
                    $list = new Redirect\Listing();
                    $list->setCondition('active = 1 AND regex = 1');
                    $list->setOrder('DESC');
                    $list->setOrderKey('priority');

                    $this->redirects = $list->load();

                    Cache::save($this->redirects, $cacheKey, ['system', 'redirect', 'route'], null, 998, true);
                } catch (Exception) {
                    $this->logger->error('Failed to load redirects');
                }
            }

            $this->lock->release();
        }

        if (!is_array($this->redirects)) {
            $this->logger->warning('Failed to load redirects', [
                'redirects' => $this->redirects,
            ]);

            $this->redirects = [];
        }

        return $this->redirects;
    }

    /**
     * @return Redirect[]
     */
    private function getRegexFilteredRedirects(bool $override = false): array
    {
        $now = time();

        return array_filter($this->getRegexRedirects(), function (Redirect $redirect) use ($override, $now) {
            // this is the case when maintenance did't deactivate the redirect yet but it is already expired
            if (!empty($redirect->getExpiry()) && $redirect->getExpiry() < $now) {
                return false;
            }

            if ($override) {
                // if override is true the priority has to be 99 which means that overriding is ok
                return $redirect->getPriority() === 99;
            }
            return $redirect->getPriority() !== 99;
        });
    }
}
