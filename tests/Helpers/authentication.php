<?php

declare(strict_types=1);

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
