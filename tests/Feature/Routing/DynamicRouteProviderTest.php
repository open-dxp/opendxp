<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Routing;

use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Model\DataObject\Data\UrlSlug;
use OpenDxp\Routing\DataObjectRoute;
use OpenDxp\Routing\DynamicRouteProvider;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Test\Factory\TestArticleFactory;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\HttpFoundation\Request;

it('matches the slug of a site without a request on the stack', function () {
    $site = SiteFactory::createOne();
    $article = TestArticleFactory::createOne(['slug' => [new UrlSlug('/green-bike', $site->getId())]]);
    $request = Request::create(sprintf('http://%s/green-bike', $site->getMainDomain()));

    Container::get(SiteResolver::class)->setSite($request, $site);
    Container::requestStack()->pop();

    $routes = Container::get(DynamicRouteProvider::class)->getRouteCollectionForRequest($request)->all();

    expect(reset($routes))
        ->toBeInstanceOf(DataObjectRoute::class)
        ->getObject()->getId()->toBe($article->getId())
        ->getSite()->getId()->toBe($site->getId());
});

it('finds the route of a slug by its name without a request on the stack', function () {
    $site = SiteFactory::createOne();
    $article = TestArticleFactory::createOne(['slug' => [new UrlSlug('/green-bike', $site->getId())]]);
    $name = sprintf('data_object_%d_%d_/green-bike', $article->getId(), $site->getId());

    Container::requestStack()->pop();

    $route = Container::get(DynamicRouteProvider::class)->getRouteByName($name);

    expect($route)->getRouteKey()->toBe($name);
});
