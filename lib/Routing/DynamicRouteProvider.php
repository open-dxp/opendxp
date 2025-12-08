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

namespace OpenDxp\Routing;

use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Routing\Dynamic\DynamicRequestContext;
use OpenDxp\Routing\Dynamic\DynamicRouteHandlerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
final class DynamicRouteProvider implements RouteProviderInterface
{
    /**
     * @var DynamicRouteHandlerInterface[]
     */
    protected array $handlers = [];

    /**
     * @param DynamicRouteHandlerInterface[] $handlers
     */
    public function __construct(protected SiteResolver $siteResolver, array $handlers = [])
    {
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    public function addHandler(DynamicRouteHandlerInterface $handler): void
    {
        if (!in_array($handler, $this->handlers, true)) {
            $this->handlers[] = $handler;
        }
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $collection = new RouteCollection();

        if ($request->attributes->has('_controller')) {
            return $collection;
        }

        $path = $originalPath = rawurldecode($request->getPathInfo());

        // site path handled by FrontendRoutingListener which runs before routing is started
        if (null !== $sitePath = $this->siteResolver->getSitePath($request)) {
            $path = $sitePath;
        }

        foreach ($this->handlers as $handler) {
            $handler->matchRequest($collection, new DynamicRequestContext($request, $path, $originalPath));
        }

        return $collection;
    }

    public function getRouteByName(string $name): SymfonyRoute
    {
        foreach ($this->handlers as $handler) {
            try {
                return $handler->getRouteByName($name);
            } catch (RouteNotFoundException) {
                // noop
            }
        }

        throw new RouteNotFoundException(sprintf("Route for name '%s' was not found", $name));
    }

    public function getRoutesByNames(?array $names = null): array
    {
        // TODO needs performance optimizations
        // TODO really return all routes here as documentation states? where is this used?
        $routes = [];

        if (is_array($names)) {
            foreach ($names as $name) {
                try {
                    $route = $this->getRouteByName($name);
                    $routes[] = $route;
                } catch (RouteNotFoundException) {
                    // noop
                }
            }
        }

        return $routes;
    }
}
