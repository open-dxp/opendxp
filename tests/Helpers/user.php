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


use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Puts the user into the security token. A controller that asks who is logged in is then handed this
 * user, the same way a backend request would hand it one.
 */
function actingAs(User $user): void
{
    Container::get(TokenStorageInterface::class)->setToken(
        new UsernamePasswordToken(new UserProxy($user), 'opendxp_admin', $user->getRoles()),
    );
}

function theAdmin(): User
{
    return User::getByName('admin');
}
