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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Unit\HttpCache;

use OpenDxp\Bundle\CoreBundle\EventListener\HttpCache\HttpCacheScopeListener;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\HttpCache\HttpCache;
use OpenDxp\HttpCache\HttpCacheScope;
use OpenDxp\Model\Document;
use OpenDxp\Routing\HttpCacheTaggableInterface;
use OpenDxp\Tests\Support\Test\TestCase;
use stdClass;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpCacheScopeListenerTest extends TestCase
{
    private HttpCache $httpCache;

    private HttpCacheScope $scope;

    private OpenDxpContextResolver $resolver;

    private DocumentResolver $documentResolver;

    private HttpCacheScopeListener $listener;

    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpCache        = $this->createMock(HttpCache::class);
        $this->scope            = $this->createMock(HttpCacheScope::class);
        $this->resolver         = $this->createMock(OpenDxpContextResolver::class);
        $this->documentResolver = $this->createMock(DocumentResolver::class);
        $this->kernel           = $this->createMock(HttpKernelInterface::class);

        $this->listener = $this->makeListener();
    }

    public function testEnablesScopeForMainRequest(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);
        $this->scope->expects($this->once())->method('enable');

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true));
    }

    public function testDoesNotEnableScopeForSubRequest(): void
    {
        $this->scope->expects($this->never())->method('enable');

        $this->listener->onKernelController($this->makeControllerEvent(isMain: false));
    }

    public function testDoesNotEnableScopeForAdminContext(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(true);
        $this->scope->expects($this->never())->method('enable');

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true));
    }

    public function testDoesNotEnableScopeForNonCacheableMethod(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);
        $this->scope->expects($this->never())->method('enable');

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true, method: 'POST'));
    }

    public function testCollectsRouteTagForMainRequest(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);

        $element = new stdClass();
        $route   = $this->createMock(HttpCacheTaggableInterface::class);
        $route->method('getCacheElement')->willReturn($element);

        $this->httpCache->expects($this->once())->method('collectTagsFor')->with($element);

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true, routeDocument: $route));
    }

    public function testSkipsRouteTagWhenElementIsNull(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);

        $route = $this->createMock(HttpCacheTaggableInterface::class);
        $route->method('getCacheElement')->willReturn(null);

        $this->httpCache->expects($this->never())->method('collectTagsFor');

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true, routeDocument: $route));
    }

    public function testSkipsRouteTagWhenRouteNotTaggable(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);
        $this->httpCache->expects($this->never())->method('collectTagsFor');

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true, routeDocument: new stdClass()));
    }

    public function testCollectsContentKeyTag(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);

        $content = new Document();
        $this->documentResolver->method('getDocument')->willReturn($content);
        $this->httpCache->expects($this->once())->method('collectTagsFor')->with($content);

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true));
    }

    public function testCollectsFallbackDocumentTagWhenRouteResolvedToNonDocumentElement(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);

        $object = new stdClass();
        $route  = $this->createMock(HttpCacheTaggableInterface::class);
        $route->method('getCacheElement')->willReturn($object);

        $content = new Document();
        $this->documentResolver->method('getDocument')->willReturn($content);

        // both the route's own element (e.g. a DataObject) and the fallback document get tagged
        $this->httpCache->expects($this->exactly(2))->method('collectTagsFor');

        $this->listener->onKernelController($this->makeControllerEvent(isMain: true, routeDocument: $route));
    }

    public function testDoesNotDoubleTagWhenRouteAlreadyResolvedToADocument(): void
    {
        $listener = $this->makeListener(tagFallbackDocument: false);
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);

        $document = new Document();
        $route    = $this->createMock(HttpCacheTaggableInterface::class);
        $route->method('getCacheElement')->willReturn($document);
        $this->documentResolver->method('getDocument')->willReturn($document);

        // must be tagged exactly once (via the route element), regardless of tagFallbackDocument
        $this->httpCache->expects($this->once())->method('collectTagsFor')->with($document);

        $listener->onKernelController($this->makeControllerEvent(isMain: true, routeDocument: $route));
    }

    public function testSkipsFallbackDocumentTagWhenTaggingDisabled(): void
    {
        $listener = $this->makeListener(tagFallbackDocument: false);
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);

        $object = new stdClass();
        $route  = $this->createMock(HttpCacheTaggableInterface::class);
        $route->method('getCacheElement')->willReturn($object);

        $content = new Document();
        $this->documentResolver->method('getDocument')->willReturn($content);

        // only the route's own element gets tagged, the fallback document is skipped
        $this->httpCache->expects($this->once())->method('collectTagsFor')->with($object);

        $listener->onKernelController($this->makeControllerEvent(isMain: true, routeDocument: $route));
    }

    public function testRequestScopeEnablesOnKernelRequest(): void
    {
        $listener = $this->makeListener(collectFromRequest: true);
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);
        $this->scope->expects($this->once())->method('enable');

        $listener->onKernelRequest($this->makeRequestEvent(isMain: true));
    }

    public function testRequestScopeDoesNotEnableForAdminContext(): void
    {
        $listener = $this->makeListener(collectFromRequest: true);
        $this->resolver->method('matchesOpenDxpContext')->willReturn(true);
        $this->scope->expects($this->never())->method('enable');

        $listener->onKernelRequest($this->makeRequestEvent(isMain: true));
    }

    public function testRequestScopeDoesNotEnableForNonCacheableMethod(): void
    {
        $listener = $this->makeListener(collectFromRequest: true);
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);
        $this->scope->expects($this->never())->method('enable');

        $listener->onKernelRequest($this->makeRequestEvent(isMain: true, method: 'POST'));
    }

    public function testRequestScopeDoesNotEnableForSubRequest(): void
    {
        $listener = $this->makeListener(collectFromRequest: true);
        $this->scope->expects($this->never())->method('enable');

        $listener->onKernelRequest($this->makeRequestEvent(isMain: false));
    }

    public function testControllerScopeDoesNotEnableOnKernelRequest(): void
    {
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);
        $this->scope->expects($this->never())->method('enable');

        $this->listener->onKernelRequest($this->makeRequestEvent(isMain: true));
    }

    public function testRequestScopeDoesNotEnableAgainOnKernelController(): void
    {
        $listener = $this->makeListener(collectFromRequest: true);
        $this->resolver->method('matchesOpenDxpContext')->willReturn(false);
        $this->scope->expects($this->never())->method('enable');

        $listener->onKernelController($this->makeControllerEvent(isMain: true));
    }

    private function makeListener(bool $collectFromRequest = false, bool $tagFallbackDocument = true): HttpCacheScopeListener
    {
        return new HttpCacheScopeListener(
            $this->httpCache,
            $this->scope,
            $this->resolver,
            $this->documentResolver,
            collectFromRequest: $collectFromRequest,
            tagFallbackDocument: $tagFallbackDocument,
        );
    }

    private function makeRequestEvent(bool $isMain, string $method = 'GET'): RequestEvent
    {
        $request = Request::create('/', $method);

        return new RequestEvent(
            $this->kernel,
            $request,
            $isMain ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        );
    }

    private function makeControllerEvent(bool $isMain, mixed $routeDocument = null, string $method = 'GET'): ControllerEvent
    {
        $request = Request::create('/', $method);
        if ($routeDocument !== null) {
            $request->attributes->set(DynamicRouter::ROUTE_KEY, $routeDocument);
        }

        return new ControllerEvent(
            $this->kernel,
            static fn () => new Response(),
            $request,
            $isMain ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        );
    }
}
