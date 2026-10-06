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

use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the request and returns the answer without following a redirect.
 */
function answerTo(string $uri): Response
{
    return Browser::start()
        ->interceptRedirects()
        ->visit($uri)
        ->client()
        ->getResponse();
}

/**
 * Symfony resets its services between two requests, and a test that sends several requests does the same.
 */
function resetServices(): void
{
    Container::get('services_resetter')->reset();
}
