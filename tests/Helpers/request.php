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


use OpenDxp\Http\RequestHelper;
use OpenDxp\TestFoundation\Container;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Serves a frontend request. OpenDXP reads the main request to resolve a path across sites, so
 * this replaces whatever the test case put on the stack instead of pushing on top of it.
 */
function frontendRequest(string $url, mixed $content = null): Request
{
    $stack = Container::requestStack();

    while ($stack->getCurrentRequest() !== null) {
        $stack->pop();
    }

    return subRequest($url, $content);
}

function subRequest(string $url, mixed $content = null): Request
{
    $request = Request::create($url);
    $request->attributes->set(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST, true);

    if ($content !== null) {
        $request->attributes->set(DynamicRouter::CONTENT_KEY, $content);
    }

    Container::requestStack()->push($request);

    return $request;
}
