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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Builds a request that carries a session from an earlier request, with the token of the admin firewall in it.
 */
function requestWithAdminToken(string $token): Request
{
    $session = new Session(new MockArraySessionStorage());
    $session->set('_security_opendxp_admin', $token);

    $request = Request::create('/admin');
    $request->setSession($session);
    $request->cookies->set($session->getName(), $session->getId());

    return $request;
}
