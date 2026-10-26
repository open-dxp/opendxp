<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\StaticRoute;

use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Test\Factory\StaticRouteFactory;

it('finds a route that another process deleted and created again', function () {
    StaticRouteFactory::createOne(['name' => 'news_detail']);
    Staticroute::getByName('news_detail')
        ->getDao()
        ->delete();
    $created = StaticRouteFactory::createOne(['name' => 'news_detail']);
    RuntimeCache::clear();

    $route = Staticroute::getByName('news_detail');

    expect($route?->getId())->toBe($created->getId());
});
