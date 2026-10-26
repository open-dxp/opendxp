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
