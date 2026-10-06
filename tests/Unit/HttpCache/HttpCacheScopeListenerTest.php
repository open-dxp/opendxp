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

function controllerEvent(HttpKernelInterface $kernel, Request $request, int $requestType): ControllerEvent
{
    return new ControllerEvent(
        $kernel,
        static fn () => new Response(),
        $request,
        $requestType,
    );
}

function routedRequest(object $route): Request
{
    $request = Request::create('/');
    $request->attributes->set(DynamicRouter::ROUTE_KEY, $route);

    return $request;
}

dataset('requests that are never cached', [
    'a sub request' => [HttpKernelInterface::SUB_REQUEST, false, 'GET'],
    'a request in the admin context' => [HttpKernelInterface::MAIN_REQUEST, true, 'GET'],
    'a request with a method that is never cached' => [HttpKernelInterface::MAIN_REQUEST, false, 'POST'],
]);

beforeEach(function () {
    $this->cache = $this->createMock(HttpCache::class);
    $this->scope = $this->createMock(HttpCacheScope::class);
    $this->context = $this->createMock(OpenDxpContextResolver::class);
    $this->documents = $this->createMock(DocumentResolver::class);
    $this->kernel = $this->createMock(HttpKernelInterface::class);
});

describe('while it collects on the controller', function () {
    beforeEach(fn () => $this->listener = new HttpCacheScopeListener(
        $this->cache,
        $this->scope,
        $this->context,
        $this->documents,
        collectFromRequest: false,
        tagFallbackDocument: true,
    ));

    it('opens the scope for a main request', function () {
        $request = Request::create('/');
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->scope
            ->expects($this->once())
            ->method('enable');

        $this->listener->onKernelController($event);
    });

    it('leaves the scope closed', function (int $requestType, bool $adminContext, string $method) {
        $this->context
            ->method('matchesOpenDxpContext')
            ->willReturn($adminContext);
        $request = Request::create('/', $method);
        $event = controllerEvent($this->kernel, $request, $requestType);

        $this->scope
            ->expects($this->never())
            ->method('enable');

        $this->listener->onKernelController($event);
    })->with('requests that are never cached');

    it('does not open the scope on the request', function () {
        $request = Request::create('/');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->scope
            ->expects($this->never())
            ->method('enable');

        $this->listener->onKernelRequest($event);
    });

    it('collects the tags of the element the route resolved to', function () {
        $element = new stdClass();
        $route = $this->createMock(HttpCacheTaggableInterface::class);
        $route
            ->method('getCacheElement')
            ->willReturn($element);
        $request = routedRequest($route);
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->cache
            ->expects($this->once())
            ->method('collectTagsFor')
            ->with($element);

        $this->listener->onKernelController($event);
    });

    it('collects no tags for a route that resolved to nothing', function () {
        $route = $this->createMock(HttpCacheTaggableInterface::class);
        $route
            ->method('getCacheElement')
            ->willReturn(null);
        $request = routedRequest($route);
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->cache
            ->expects($this->never())
            ->method('collectTagsFor');

        $this->listener->onKernelController($event);
    });

    it('collects no tags for a route that cannot be tagged', function () {
        $request = routedRequest(new stdClass());
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->cache
            ->expects($this->never())
            ->method('collectTagsFor');

        $this->listener->onKernelController($event);
    });

    it('collects the tags of the document the request resolved to', function () {
        $document = new Document();
        $this->documents
            ->method('getDocument')
            ->willReturn($document);
        $request = Request::create('/');
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->cache
            ->expects($this->once())
            ->method('collectTagsFor')
            ->with($document);

        $this->listener->onKernelController($event);
    });

    it('collects the fallback document too when the route element is not a document', function () {
        $element = new stdClass();
        $document = new Document();
        $route = $this->createMock(HttpCacheTaggableInterface::class);
        $route
            ->method('getCacheElement')
            ->willReturn($element);
        $this->documents
            ->method('getDocument')
            ->willReturn($document);
        $collected = [];
        $this->cache
            ->method('collectTagsFor')
            ->willReturnCallback(function (object $tagged) use (&$collected) {
                $collected[] = $tagged;
            });
        $request = routedRequest($route);
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelController($event);

        expect($collected)->toBe([
            $element,
            $document,
        ]);
    });

    it('collects a document the route resolved to only once', function () {
        $document = new Document();
        $route = $this->createMock(HttpCacheTaggableInterface::class);
        $route
            ->method('getCacheElement')
            ->willReturn($document);
        $this->documents
            ->method('getDocument')
            ->willReturn($document);
        $request = routedRequest($route);
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->cache
            ->expects($this->once())
            ->method('collectTagsFor')
            ->with($document);

        $this->listener->onKernelController($event);
    });
});

describe('while it collects from the request', function () {
    beforeEach(fn () => $this->listener = new HttpCacheScopeListener(
        $this->cache,
        $this->scope,
        $this->context,
        $this->documents,
        collectFromRequest: true,
        tagFallbackDocument: true,
    ));

    it('opens the scope on the request', function () {
        $request = Request::create('/');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->scope
            ->expects($this->once())
            ->method('enable');

        $this->listener->onKernelRequest($event);
    });

    it('leaves the scope closed', function (int $requestType, bool $adminContext, string $method) {
        $this->context
            ->method('matchesOpenDxpContext')
            ->willReturn($adminContext);
        $request = Request::create('/', $method);
        $event = new RequestEvent($this->kernel, $request, $requestType);

        $this->scope
            ->expects($this->never())
            ->method('enable');

        $this->listener->onKernelRequest($event);
    })->with('requests that are never cached');

    it('does not open the scope again on the controller', function () {
        $request = Request::create('/');
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->scope
            ->expects($this->never())
            ->method('enable');

        $this->listener->onKernelController($event);
    });
});

describe('while it leaves the fallback document untagged', function () {
    beforeEach(fn () => $this->listener = new HttpCacheScopeListener(
        $this->cache,
        $this->scope,
        $this->context,
        $this->documents,
        collectFromRequest: false,
        tagFallbackDocument: false,
    ));

    it('collects only the element of the route', function () {
        $element = new stdClass();
        $route = $this->createMock(HttpCacheTaggableInterface::class);
        $route
            ->method('getCacheElement')
            ->willReturn($element);
        $this->documents
            ->method('getDocument')
            ->willReturn(new Document());
        $request = routedRequest($route);
        $event = controllerEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->cache
            ->expects($this->once())
            ->method('collectTagsFor')
            ->with($element);

        $this->listener->onKernelController($event);
    });
});
