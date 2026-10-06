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
