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


use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Bundle\StaticRoutesBundle\Routing\Staticroute\Router;
use OpenDxp\Config;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Routing\Dynamic\DynamicRequestContext;
use OpenDxp\Routing\Dynamic\DynamicRouteHandlerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @param array<string, Route> $routes
 */
function routeHandler(array $routes): DynamicRouteHandlerInterface
{
    return new class($routes) implements DynamicRouteHandlerInterface {
        /**
         * @param array<string, Route> $routes
         */
        public function __construct(private array $routes)
        {
        }

        public function getRouteByName(string $name): ?Route
        {
            return $this->routes[$name] ?? throw new RouteNotFoundException();
        }

        public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void
        {
        }
    };
}

function staticroute(string $pattern, string $variables): Staticroute
{
    $route = new Staticroute();
    $route->setName('test_route');
    $route->setPattern($pattern);
    $route->setVariables($variables);
    $route->setController('App\\Controller\\TestController::testAction');

    return $route;
}

function staticrouteRouter(Staticroute $route): Router
{
    $context = new RequestContext();
    $context->setParameter('_locale', 'en');

    $router = new Router($context, new Config(), new GeneralHostResolver());

    // A unit test has no database, so the route goes in directly.
    (new ReflectionProperty(Router::class, 'staticRoutes'))->setValue($router, [$route]);

    return $router;
}
