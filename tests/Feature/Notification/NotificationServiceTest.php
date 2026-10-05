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


namespace OpenDxp\Tests\Feature\Notification;

use OpenDxp\Model\Notification\Service\NotificationService;
use OpenDxp\Test\Factory\UserRoleFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;
use UnexpectedValueException;

beforeEach(fn () => $this->notifications = Container::get(NotificationService::class));

it('refuses to send to a user that does not exist', function () {
    $this->notifications->sendToUser(100, 100, 'Test title', 'Test message');
})->throws(UnexpectedValueException::class, 'No user found with the ID 100');

it('refuses to send to a group that does not exist', function () {
    $this->notifications->sendToGroup(100, 100, 'Test title', 'Test message');
})->throws(UnexpectedValueException::class, 'No group found with the ID 100');

it('leaves one notification per message it sent to a user', function () {

    $user = UserFactory::createOne();

    $this->notifications->sendToUser($user->getId(), 0, 'Test title', 'Test message');
    $this->notifications->sendToUser($user->getId(), 0, 'Test title', 'Test message');

    expect($this->notifications->findAll(['recipient' => $user->getId()])['total'])->toBe(2);
});

it('leaves a notification that points at the element it was sent about', function () {

    $user = UserFactory::createOne();
    $object = UnittestFactory::createOne();

    $this->notifications->sendToUser($user->getId(), 0, 'Test title', 'Test message', $object);

    $found = $this->notifications->findAll(['recipient' => $user->getId()]);

    expect($found['total'])
        ->toBe(1)
        ->and($found['data'][0]->getLinkedElement()->getId())
        ->toBe($object->getId());
});

it('leaves a notification with every user of the group it was sent to', function () {

    // sendToGroup skips a user who is not allowed to see notifications.
    $group = UserRoleFactory::createOne(['permissions' => ['notifications']]);
    $first = UserFactory::createOne(['roles' => [$group->getId()]]);
    $second = UserFactory::createOne(['roles' => [$group->getId()]]);

    $this->notifications->sendToGroup($group->getId(), 0, 'Test title', 'Test message');

    expect($this->notifications->findAll(['recipient' => $first->getId()])['total'])
        ->toBe(1)
        ->and($this->notifications->findAll(['recipient' => $second->getId()])['total'])
        ->toBe(1);
});
