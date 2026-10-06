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

use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Bundle\StaticRoutesBundle\Routing\Staticroute\Router;
use OpenDxp\Config;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use ReflectionProperty;
use Symfony\Component\Routing\RequestContext;

function staticroute(string $variables): Staticroute
{
    $route = new Staticroute();
    $route->setName('test_route');
    $route->setPattern('/\/(\w+)\/product$/');
    $route->setVariables($variables);
    $route->setController('App\\Controller\\TestController::testAction');

    return $route;
}

function staticrouteRouter(Staticroute $route): Router
{
    $context = new RequestContext();
    $context->setParameter('_locale', 'en');

    $router = new Router(
        $context,
        new Config(),
        new GeneralHostResolver(),
    );

    // The router is final and loads its routes from the database, which a unit test does not have.
    $routes = new ReflectionProperty(Router::class, 'staticRoutes');
    $routes->setValue($router, [$route]);

    return $router;
}

afterEach(fn () => Staticroute::setCurrentRoute(null));

it('leaves the locale of the context out of the matched parameters', function () {
    $router = staticrouteRouter(staticroute('lang'));

    $parameters = $router->match('/de/product');

    expect($parameters)
        ->toHaveKey('lang', 'de')
        ->not->toHaveKey('_locale');
});

it('keeps a locale the pattern itself matched', function () {
    $router = staticrouteRouter(staticroute('_locale'));

    $parameters = $router->match('/de/product');

    expect($parameters)->toHaveKey('_locale', 'de');
});

it('reads the locale from the variable a route declares for it', function () {
    $router = staticrouteRouter(staticroute('language'));
    $router->setLocaleParams(['language']);

    $parameters = $router->match('/de/product');

    expect($parameters)->toHaveKey('_locale', 'de');
});
