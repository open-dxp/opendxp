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
use OpenDxp\Bundle\SeoBundle\Event\Model\RedirectEvent;
use OpenDxp\Bundle\SeoBundle\Event\RedirectEvents;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Helper\StringHelper;
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
        private RedirectTableProvider $tables,
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

        // get current site if available
        if (!$sourceSite && $this->siteResolver->isSiteRequest($request)) {
            $sourceSite = $this->siteResolver->getSite($request);
        }

        $table = $this->tables->get();
        $stage = $override ? RedirectTable::BEFORE_ROUTING : RedirectTable::NOT_FOUND;
        $partResolver = new RedirectUrlPartResolver($request);
        $now = time();

        $exactMatch = $table->exactMatch($stage, $sourceSite?->getId(), $partResolver, $now);
        $exactMatchIsProtected = !empty($exactMatch['protected']);

        if ($exactMatch !== null && $exactMatchIsProtected && ($response = $this->buildRedirectResponse($this->hydrate($exactMatch), $request)) instanceof Response) {
            return $response;
        }

        if (($response = $this->matchRegularExpressions($table->regularExpressions($stage, true), $request, $partResolver, $sourceSite, $now)) instanceof Response) {
            return $response;
        }

        if ($exactMatch !== null && !$exactMatchIsProtected && ($response = $this->buildRedirectResponse($this->hydrate($exactMatch), $request)) instanceof Response) {
            return $response;
        }

        return $this->matchRegularExpressions($table->regularExpressions($stage, false), $request, $partResolver, $sourceSite, $now);
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

        $row = $this->tables->get()->domainMatch($request->getHost(), time());

        return $row === null ? null : $this->buildRedirectResponse($this->hydrate($row), $request);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @throws Exception
     */
    private function matchRegularExpressions(array $rows, Request $request, RedirectUrlPartResolver $partResolver, ?Site $sourceSite, int $now): ?Response
    {
        foreach ($rows as $row) {
            // this is the case when maintenance did't deactivate the redirect yet but it is already expired
            if ((!empty($row['expiry']) && (int) $row['expiry'] < $now) || !RedirectTable::hasStarted($row, $now)) {
                continue;
            }

            if (($response = $this->matchRegexRedirect($row, $request, $partResolver, $sourceSite)) instanceof Response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws Exception
     */
    private function matchRegexRedirect(
        array $row,
        Request $request,
        RedirectUrlPartResolver $partResolver,
        ?Site $sourceSite = null
    ): ?Response {
        $matches = [];
        if (!@preg_match((string) $row['source'], $partResolver->getRequestUriPart($row['type']), $matches)) {
            return null;
        }

        // check for a site
        $redirectSite = (int) ($row['sourceSite'] ?? 0);
        if (($redirectSite || $sourceSite) && (!$sourceSite || $sourceSite->getId() !== $redirectSite)) {
            return null;
        }

        return $this->buildRedirectResponse($this->hydrate($row), $request, $matches);
    }

    /**
     * Turns a row of the redirect table into the model that Redirect::getById() would load.
     *
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Redirect
    {
        $redirect = new Redirect();
        $redirect->setValues($row);

        return $redirect;
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
                $redirectDomain = $site instanceof Site ? $request->getHost() : $this->generalHostResolver->resolve(['source' => $request]);

                if ($redirectDomain) {
                    // prepend the host and scheme to avoid infinite loops when using "domain" redirects
                    $url = $request->getScheme().'://'.$redirectDomain.$url;
                }
            }
        }

        if ($redirect->getType() === Redirect::TYPE_DOMAIN && $redirect->getPassThroughPath()) {
            $url = rtrim($url, '/') . $request->getPathInfo();
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
