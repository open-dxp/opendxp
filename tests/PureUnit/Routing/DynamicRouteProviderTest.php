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

namespace OpenDxp\Tests\PureUnit\Routing;

use Codeception\Test\Unit as TestCase;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Routing\Dynamic\DynamicRequestContext;
use OpenDxp\Routing\Dynamic\DynamicRouteHandlerInterface;
use OpenDxp\Routing\DynamicRouteProvider;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Pins down {@see DynamicRouteProvider::getRoutesByNames()} so the planned
 * batch-prefetch optimization preserves: handler chain dispatch, ordering of
 * results, and silent-skip on RouteNotFoundException.
 *
 * @group unit.routing.dynamic-route-provider
 */
class DynamicRouteProviderTest extends TestCase
{
    private SiteResolver $siteResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteResolver = $this->createMock(SiteResolver::class);
    }

    public function testReturnsEmptyArrayForNullNames(): void
    {
        $provider = new DynamicRouteProvider($this->siteResolver, []);
        $this->assertSame([], $provider->getRoutesByNames(null));
    }

    public function testReturnsEmptyArrayForEmptyNamesArray(): void
    {
        $provider = new DynamicRouteProvider($this->siteResolver, []);
        $this->assertSame([], $provider->getRoutesByNames([]));
    }

    public function testReturnsRoutesInRequestedOrder(): void
    {
        $routeA = new Route('/a');
        $routeB = new Route('/b');
        $handler = new class([
            'route_a' => $routeA,
            'route_b' => $routeB,
        ]) implements DynamicRouteHandlerInterface {
            public function __construct(private array $routes)
            {
            }

            public function getRouteByName(string $name): ?Route
            {
                if (!isset($this->routes[$name])) {
                    throw new RouteNotFoundException();
                }

                return $this->routes[$name];
            }

            public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void
            {
            }
        };

        $provider = new DynamicRouteProvider($this->siteResolver, [$handler]);
        $routes = $provider->getRoutesByNames(['route_b', 'route_a']);

        $this->assertCount(2, $routes);
        $this->assertSame($routeB, $routes[0]);
        $this->assertSame($routeA, $routes[1]);
    }

    public function testSilentlySkipsRouteNotFoundExceptions(): void
    {
        $routeA = new Route('/a');
        $handler = new class($routeA) implements DynamicRouteHandlerInterface {
            public function __construct(private Route $route)
            {
            }

            public function getRouteByName(string $name): ?Route
            {
                if ($name === 'exists') {
                    return $this->route;
                }

                throw new RouteNotFoundException();
            }

            public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void
            {
            }
        };

        $provider = new DynamicRouteProvider($this->siteResolver, [$handler]);
        $routes = $provider->getRoutesByNames(['missing_one', 'exists', 'missing_two']);

        $this->assertCount(1, $routes);
        $this->assertSame($routeA, $routes[0]);
    }

    public function testWalksHandlerChainUntilOneMatches(): void
    {
        $routeFromSecond = new Route('/second');
        $firstHandler = new class implements DynamicRouteHandlerInterface {
            public function getRouteByName(string $name): ?Route
            {
                throw new RouteNotFoundException();
            }

            public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void
            {
            }
        };
        $secondHandler = new class($routeFromSecond) implements DynamicRouteHandlerInterface {
            public function __construct(private Route $route)
            {
            }

            public function getRouteByName(string $name): ?Route
            {
                return $this->route;
            }

            public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void
            {
            }
        };

        $provider = new DynamicRouteProvider($this->siteResolver, [$firstHandler, $secondHandler]);
        $routes = $provider->getRoutesByNames(['anything']);

        $this->assertCount(1, $routes);
        $this->assertSame($routeFromSecond, $routes[0]);
    }

    public function testGetRouteByNameThrowsWhenNoHandlerMatches(): void
    {
        $handler = new class implements DynamicRouteHandlerInterface {
            public function getRouteByName(string $name): ?Route
            {
                throw new RouteNotFoundException();
            }

            public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void
            {
            }
        };

        $provider = new DynamicRouteProvider($this->siteResolver, [$handler]);

        $this->expectException(RouteNotFoundException::class);
        $provider->getRouteByName('any');
    }
}
