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


use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Controller\SearchController;
use OpenDxp\Model\User;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Runs the backend search as the named user and hands back the paths it answered with.
 */
function searchAs(string $userName, string $type, string $query, int $limit = 100): array
{
    actingAs(User::getByName($userName));

    $response = Container::get(SearchController::class)->findAction(
        new Request(['type' => $type, 'query' => $query, 'start' => 0, 'limit' => $limit]),
        Container::get(EventDispatcherInterface::class),
        Container::get(GridHelperService::class),
    );

    $answered = json_decode($response->getContent(), true);

    expect($answered['data'])->toHaveCount($answered['total'], 'the total does not match the nodes');

    return array_column($answered['data'], 'fullpath');
}

/**
 * Runs the backend quick search as the named user and hands back the paths it answered with. The
 * quick search reaches every kind of element at once, so it takes no type.
 */
function quickSearchAs(string $userName, string $query, int $limit = 100): array
{
    actingAs(User::getByName($userName));

    $response = Container::get(SearchController::class)->quicksearchAction(
        new Request(['query' => $query, 'start' => 0, 'limit' => $limit]),
        Container::get(EventDispatcherInterface::class),
    );

    return array_column(json_decode($response->getContent(), true)['data'], 'fullpathList');
}
