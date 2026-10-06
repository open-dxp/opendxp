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
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;
use UnexpectedValueException;

function notificationCountOf(int $userId): int
{
    $found = Container::get(NotificationService::class)->findAll(['recipient' => $userId]);

    return $found['total'];
}

beforeEach(function () {
    $this->notifications = Container::get(NotificationService::class);
});

it('refuses to send to a user that does not exist', function () {
    $this->notifications->sendToUser(999999, 0, 'Title', 'Message');
})->throws(UnexpectedValueException::class, 'No user found with the ID 999999');

it('refuses to send to a group that does not exist', function () {
    $this->notifications->sendToGroup(999999, 0, 'Title', 'Message');
})->throws(UnexpectedValueException::class, 'No group found with the ID 999999');

it('keeps an earlier notification when it sends another one', function () {
    $userId = UserFactory::createOne()->getId();
    $this->notifications->sendToUser($userId, 0, 'Title', 'Message');

    $this->notifications->sendToUser($userId, 0, 'Title', 'Message');

    expect(notificationCountOf($userId))->toBe(2);
});

it('links a notification to the element it was sent about', function () {
    $userId = UserFactory::createOne()->getId();
    $object = UnittestFactory::createOne();

    $this->notifications->sendToUser($userId, 0, 'Title', 'Message', $object);

    $found = $this->notifications->findAll(['recipient' => $userId]);
    expect($found['total'])
        ->toBe(1)
        ->and($found['data'][0]->getLinkedElement())
        ->getId()
        ->toBe($object->getId());
});

it('sends a notification to every user of a group', function () {
    // A group passes a notification on only to users who may see notifications.
    $group = UserRoleFactory::new()
        ->withPermissions('notifications')
        ->create();
    $first = UserFactory::new()
        ->withRoles($group)
        ->create();
    $second = UserFactory::new()
        ->withRoles($group)
        ->create();

    $this->notifications->sendToGroup(
        $group->getId(),
        0,
        'Title',
        'Message',
    );

    expect(notificationCountOf($first->getId()))
        ->toBe(1)
        ->and(notificationCountOf($second->getId()))
        ->toBe(1);
});
