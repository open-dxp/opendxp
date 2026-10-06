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

namespace OpenDxp\Tests\Feature\Tool;

use OpenDxp\Security\User\User as SecurityUser;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tool\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

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

it('signs in a user whose access is restricted to a workspace', function () {
    $objects = DataObjectFolderFactory::createOne();
    $documents = DocumentFolderFactory::createOne();
    // An administrator carries no workspace, so the restrictions would never be unserialized.
    $user = UserFactory::new()
        ->withPermissions(
            'objects',
            'documents',
        )
        ->withObjectWorkspace($objects, 'list', 'view')
        ->withDocumentWorkspace($documents, 'list', 'view')
        ->create();
    $securityUser = new SecurityUser($user);
    $token = new PostAuthenticationToken(
        $securityUser,
        'opendxp_admin',
        $securityUser->getRoles(),
    );
    $request = requestWithAdminToken(serialize($token));

    $signedIn = Authentication::authenticateSession($request);

    expect($signedIn)
        ->getId()
        ->toBe($user->getId());
});

it('signs no one in for a token it cannot unserialize', function (string $token) {
    $request = requestWithAdminToken($token);

    $signedIn = Authentication::authenticateSession($request);

    expect($signedIn)->toBeNull();
})->with([
    'a token that is no serialized data' => ['this is not a valid serialized payload at all'],
    'a token naming a class that does not exist' => ['O:34:"Totally\Nonexistent\FakeClassXyz":0:{}'],
]);
