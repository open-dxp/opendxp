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

namespace OpenDxp\Tests\Application\Routing;

use OpenDxp\Routing\Dynamic\DynamicRequestContext;
use OpenDxp\Routing\Dynamic\DynamicRouteHandlerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final readonly class MockDynamicRouteHandler implements DynamicRouteHandlerInterface
{
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
}
