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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

beforeEach(function () {
    $this->siteResolver = $this->createMock(SiteResolver::class);
});

it('answers with nothing when no name is asked for', function (?array $names) {
    expect((new DynamicRouteProvider($this->siteResolver, []))->getRoutesByNames($names))->toBe([]);
})->with([
    'no list at all' => [null],
    'an empty list' => [[]],
]);

it('answers in the order the names were asked for', function () {

    $first = new Route('/a');
    $second = new Route('/b');
    $provider = new DynamicRouteProvider($this->siteResolver, [
        routeHandler(['route_a' => $first, 'route_b' => $second]),
    ]);

    expect($provider->getRoutesByNames(['route_b', 'route_a']))->toBe([$second, $first]);
});

it('leaves out a name no handler knows', function () {

    $route = new Route('/a');
    $provider = new DynamicRouteProvider($this->siteResolver, [routeHandler(['exists' => $route])]);

    expect($provider->getRoutesByNames(['missing_one', 'exists', 'missing_two']))->toBe([$route]);
});

it('asks the next handler when the one before knows nothing', function () {

    $route = new Route('/second');
    $provider = new DynamicRouteProvider($this->siteResolver, [
        routeHandler([]),
        routeHandler(['anything' => $route]),
    ]);

    expect($provider->getRoutesByNames(['anything']))->toBe([$route]);
});

it('refuses a single name no handler knows', function () {
    (new DynamicRouteProvider($this->siteResolver, [routeHandler([])]))->getRouteByName('any');
})->throws(RouteNotFoundException::class);
