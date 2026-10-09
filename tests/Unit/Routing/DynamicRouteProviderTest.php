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

namespace OpenDxp\Tests\Unit\Routing;

use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Routing\DynamicRouteProvider;
use OpenDxp\Tests\Application\Routing\MockDynamicRouteHandler;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

beforeEach(fn () => $this->siteResolver = $this->createMock(SiteResolver::class));

it('returns no routes when no names are asked for', function (?array $names) {
    $provider = new DynamicRouteProvider($this->siteResolver, []);

    $routes = $provider->getRoutesByNames($names);

    expect($routes)->toBe([]);
})->with([
    'no list at all' => [null],
    'an empty list' => [[]],
]);

it('returns the routes in the order the names were asked for', function () {
    $first = new Route('/a');
    $second = new Route('/b');
    $handler = new MockDynamicRouteHandler([
        'route_a' => $first,
        'route_b' => $second,
    ]);
    $provider = new DynamicRouteProvider($this->siteResolver, [$handler]);

    $routes = $provider->getRoutesByNames([
        'route_b',
        'route_a',
    ]);

    expect($routes)->toBe([
        $second,
        $first,
    ]);
});

it('leaves out a name no handler knows', function () {
    $route = new Route('/a');
    $handler = new MockDynamicRouteHandler(['exists' => $route]);
    $provider = new DynamicRouteProvider($this->siteResolver, [$handler]);

    $routes = $provider->getRoutesByNames([
        'missing_one',
        'exists',
        'missing_two',
    ]);

    expect($routes)->toBe([$route]);
});

it('asks the next handler when the first one does not know a name', function () {
    $route = new Route('/second');
    $provider = new DynamicRouteProvider(
        $this->siteResolver,
        [
            new MockDynamicRouteHandler([]),
            new MockDynamicRouteHandler(['anything' => $route]),
        ],
    );

    $routes = $provider->getRoutesByNames(['anything']);

    expect($routes)->toBe([$route]);
});

it('refuses a single name no handler knows', function () {
    $provider = new DynamicRouteProvider($this->siteResolver, [new MockDynamicRouteHandler([])]);

    expect(fn () => $provider->getRouteByName('any'))
        ->toThrow(RouteNotFoundException::class, "Route for name 'any' was not found");
});
