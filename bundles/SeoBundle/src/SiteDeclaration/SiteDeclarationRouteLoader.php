<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\SiteDeclaration;

use OpenDxp\Bundle\SeoBundle\Controller\SiteDeclarationController;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class SiteDeclarationRouteLoader implements RouteLoaderInterface
{
    public function __construct(private readonly SiteDeclarationRegistry $registry)
    {
    }

    public function loadRoutes(): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($this->registry->getDeclarations() as $name => $declaration) {
            $route = new Route($declaration->getPath(), defaults: [
                '_controller' => SiteDeclarationController::class,
                '_site_declaration' => $name,
            ]);

            // The regex of _opendxp_service_common_files also matches /robots.txt and is loaded first.
            $routes->add(sprintf('_opendxp_service_%s', $name), $route, priority: 1);
            $routes->addResource(new FileResource((new ReflectionClass($declaration))->getFileName()));
        }

        return $routes;
    }
}
