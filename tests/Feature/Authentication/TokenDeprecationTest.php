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

namespace OpenDxp\Tests\Feature\Authentication;

use OpenDxp\Security\User\User as SecurityUser;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tests\Application\Security\MockTokenAttribute;
use OpenDxp\Tool\Authentication;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

it('signs the user in and hands a deprecation to the previous handler while the token is unserialized', function () {
    $user = UserFactory::createOne();
    $securityUser = new SecurityUser($user);
    $token = new PostAuthenticationToken(
        $securityUser,
        'opendxp_admin',
        $securityUser->getRoles(),
    );
    $token->setAttribute('attribute', new MockTokenAttribute());
    $request = requestWithAdminToken(serialize($token));
    $deprecations = [];
    set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    });

    $signedIn = Authentication::authenticateSession($request);

    expect($signedIn)
        ->getId()
        ->toBe($user->getId())
        ->and($deprecations)
        ->toBe(['A class loaded while the token is unserialized is deprecated.']);
})->after(fn () => restore_error_handler());
