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
use OpenDxp\Security\User\UserProvider;
use OpenDxp\Test\Factory\UserFactory;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

function storedInSession(SecurityUser $user): SecurityUser
{
    return unserialize(serialize($user));
}

it('keeps the session of an unchanged user', function () {
    $session = storedInSession(new SecurityUser(UserFactory::createOne()));

    $refreshed = (new UserProvider())->refreshUser($session);

    expect($refreshed->isEqualTo($session))->toBeTrue();
});

it('ends the session when the user changes the password', function () {
    $user = UserFactory::createOne();
    $session = storedInSession(new SecurityUser($user));
    $user->setPassword('changed')->save();

    $refreshed = (new UserProvider())->refreshUser($session);

    expect($refreshed->isEqualTo($session))->toBeFalse();
});

it('ends the session when the user is deactivated', function () {
    $user = UserFactory::createOne();
    $session = storedInSession(new SecurityUser($user));
    $user->setActive(false)->save();

    $refreshed = (new UserProvider())->refreshUser($session);

    expect($refreshed->isEqualTo($session))->toBeFalse();
});

it('ends the session when the user is deleted', function () {
    $user = UserFactory::createOne();
    $session = storedInSession(new SecurityUser($user));
    $user->delete();

    (new UserProvider())->refreshUser($session);
})->throws(UserNotFoundException::class);
