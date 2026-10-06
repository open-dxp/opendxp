<?php

declare(strict_types=1);

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
