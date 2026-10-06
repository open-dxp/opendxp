<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Test\Factory\StaticRouteFactory;
use OpenDxp\TestFoundation\Controller\DefaultController;

it('writes a static route with its pattern and its controller', function () {
    StaticRouteFactory::new()
        ->withPattern('/news/%text', '/news/%text')
        ->withController(DefaultController::class, 'defaultAction')
        ->create(['name' => 'news_detail']);

    expect(Staticroute::getByName('news_detail'))
        ->getPattern()
        ->toBe('/news/%text')
        ->getController()
        ->toBe(sprintf('%s::defaultAction', DefaultController::class));
});
