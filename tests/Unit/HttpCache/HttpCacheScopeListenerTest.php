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
use stdClass;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

beforeEach(function () {
    $this->cache = $this->createMock(HttpCache::class);
    $this->scope = $this->createMock(HttpCacheScope::class);
    $this->context = $this->createMock(OpenDxpContextResolver::class);
    $this->documents = $this->createMock(DocumentResolver::class);
    $this->kernel = $this->createMock(HttpKernelInterface::class);

    $this->makeListener = fn (bool $fromRequest = false, bool $tagFallback = true) => new HttpCacheScopeListener(
        $this->cache,
        $this->scope,
        $this->context,
        $this->documents,
        collectFromRequest: $fromRequest,
        tagFallbackDocument: $tagFallback,
    );

    $this->request = fn (bool $isMain, string $method = 'GET') => new RequestEvent(
        $this->kernel,
        Request::create('/', $method),
        $isMain ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
    );

    $this->controller = function (bool $isMain, mixed $route = null, string $method = 'GET') {
        $request = Request::create('/', $method);

        if ($route !== null) {
            $request->attributes->set(DynamicRouter::ROUTE_KEY, $route);
        }

        return new ControllerEvent(
            $this->kernel,
            static fn () => new Response(),
            $request,
            $isMain ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        );
    };
});

it('opens the scope for a request a controller answers', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $this->scope->expects($this->once())->method('enable');

    ($this->makeListener)()->onKernelController(($this->controller)(isMain: true));
});

it('leaves the scope closed', function (bool $isMain, bool $adminContext, string $method) {

    $this->context->method('matchesOpenDxpContext')->willReturn($adminContext);
    $this->scope->expects($this->never())->method('enable');

    ($this->makeListener)()->onKernelController(($this->controller)(isMain: $isMain, method: $method));
})->with([
    'for a sub request' => [false, false, 'GET'],
    'in the admin context' => [true, true, 'GET'],
    'for a method that is never cached' => [true, false, 'POST'],
]);

it('collects the tags of the element a route resolved to', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $element = new stdClass();
    $route = $this->createMock(HttpCacheTaggableInterface::class);
    $route->method('getCacheElement')->willReturn($element);

    $this->cache->expects($this->once())->method('collectTagsFor')->with($element);

    ($this->makeListener)()->onKernelController(($this->controller)(isMain: true, route: $route));
});

it('collects no tags for a route that resolved to nothing', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $route = $this->createMock(HttpCacheTaggableInterface::class);
    $route->method('getCacheElement')->willReturn(null);

    $this->cache->expects($this->never())->method('collectTagsFor');

    ($this->makeListener)()->onKernelController(($this->controller)(isMain: true, route: $route));
});

it('collects no tags for a route that carries none', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $this->cache->expects($this->never())->method('collectTagsFor');

    ($this->makeListener)()->onKernelController(($this->controller)(isMain: true, route: new stdClass()));
});

it('collects the tags of the document the request resolved to', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $document = new Document();
    $this->documents->method('getDocument')->willReturn($document);

    $this->cache->expects($this->once())->method('collectTagsFor')->with($document);

    ($this->makeListener)()->onKernelController(($this->controller)(isMain: true));
});

it('collects the document next to an element that is not one', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $route = $this->createMock(HttpCacheTaggableInterface::class);
    $route->method('getCacheElement')->willReturn(new stdClass());
    $this->documents->method('getDocument')->willReturn(new Document());

    $this->cache->expects($this->exactly(2))->method('collectTagsFor');

    ($this->makeListener)()->onKernelController(($this->controller)(isMain: true, route: $route));
});

it('collects a document the route already resolved to only once', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $document = new Document();
    $route = $this->createMock(HttpCacheTaggableInterface::class);
    $route->method('getCacheElement')->willReturn($document);
    $this->documents->method('getDocument')->willReturn($document);

    $this->cache->expects($this->once())->method('collectTagsFor')->with($document);

    ($this->makeListener)(tagFallback: false)->onKernelController(($this->controller)(isMain: true, route: $route));
});

it('leaves the document out while tagging it is turned off', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $element = new stdClass();
    $route = $this->createMock(HttpCacheTaggableInterface::class);
    $route->method('getCacheElement')->willReturn($element);
    $this->documents->method('getDocument')->willReturn(new Document());

    $this->cache->expects($this->once())->method('collectTagsFor')->with($element);

    ($this->makeListener)(tagFallback: false)->onKernelController(($this->controller)(isMain: true, route: $route));
});

it('opens the scope on the request already when it collects from the request', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $this->scope->expects($this->once())->method('enable');

    ($this->makeListener)(fromRequest: true)->onKernelRequest(($this->request)(isMain: true));
});

it('leaves the scope closed on the request while it collects from the request', function (bool $isMain, bool $adminContext, string $method) {

    $this->context->method('matchesOpenDxpContext')->willReturn($adminContext);
    $this->scope->expects($this->never())->method('enable');

    ($this->makeListener)(fromRequest: true)->onKernelRequest(($this->request)(isMain: $isMain, method: $method));
})->with([
    'in the admin context' => [true, true, 'GET'],
    'for a method that is never cached' => [true, false, 'POST'],
    'for a sub request' => [false, false, 'GET'],
]);

it('leaves the scope closed on the request while it collects from the controller', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $this->scope->expects($this->never())->method('enable');

    ($this->makeListener)()->onKernelRequest(($this->request)(isMain: true));
});

it('opens the scope once, not again on the controller', function () {

    $this->context->method('matchesOpenDxpContext')->willReturn(false);
    $this->scope->expects($this->never())->method('enable');

    ($this->makeListener)(fromRequest: true)->onKernelController(($this->controller)(isMain: true));
});
