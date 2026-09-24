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

namespace OpenDxp\Tests\PureUnit\Routing;

use Codeception\Test\Unit as TestCase;
use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Bundle\StaticRoutesBundle\Routing\Staticroute\Router;
use OpenDxp\Config;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use ReflectionProperty;
use Symfony\Component\Routing\RequestContext;

/**
 * @group unit.routing.staticroute-router
 */
class StaticrouteRouterTest extends TestCase
{
    protected function tearDown(): void
    {
        Staticroute::setCurrentRoute(null);
        parent::tearDown();
    }

    public function testDefaultLocaleFromContextIsNotAddedToRouteParams(): void
    {
        $router = $this->createRouter($this->createRoute('/\/(\w+)\/product$/', 'lang'));

        $params = $router->match('/de/product');

        $this->assertSame('de', $params['lang']);
        $this->assertArrayNotHasKey('_locale', $params);
    }

    public function testMatchedLocaleVariableIsKept(): void
    {
        $router = $this->createRouter($this->createRoute('/\/(\w+)\/product$/', '_locale'));

        $params = $router->match('/de/product');

        $this->assertSame('de', $params['_locale']);
    }

    public function testLocaleParamIsMappedToLocale(): void
    {
        $router = $this->createRouter($this->createRoute('/\/(\w+)\/product$/', 'language'));
        $router->setLocaleParams(['language']);

        $params = $router->match('/de/product');

        $this->assertSame('de', $params['_locale']);
    }

    private function createRoute(string $pattern, string $variables): Staticroute
    {
        $route = new Staticroute();
        $route->setName('test_route');
        $route->setPattern($pattern);
        $route->setVariables($variables);
        $route->setController('App\Controller\TestController::testAction');

        return $route;
    }

    private function createRouter(Staticroute $route): Router
    {
        $context = new RequestContext();
        $context->setParameter('_locale', 'en');

        $router = new Router($context, new Config(), new GeneralHostResolver());

        $staticRoutes = new ReflectionProperty(Router::class, 'staticRoutes');
        $staticRoutes->setValue($router, [$route]);

        return $router;
    }
}
