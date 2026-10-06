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

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tool\Authentication;

it('writes a user who is no administrator', function () {
    $user = UserFactory::createOne();

    expect(User::getById($user->getId()))
        ->toBeInstanceOf(User::class)
        ->isAdmin()
        ->toBeFalse();
});

it('writes an administrator on request', function () {
    $admin = UserFactory::new()
        ->admin()
        ->create();

    expect(User::getById($admin->getId()))->isAdmin()->toBeTrue();
});

it('writes a user who signs in with the password a test gives', function () {
    $user = UserFactory::new()
        ->withPassword('correct horse battery staple')
        ->create();

    $verified = Authentication::verifyPassword($user, 'correct horse battery staple');

    expect($verified)->toBeTrue();
});
