<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Notification;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\NotificationFactory;
use OpenDxp\Test\Factory\UserFactory;

it('writes a notification to a user about an element', function () {
    $editor = UserFactory::createOne();
    $page = DocumentPageFactory::createOne();

    $notification = NotificationFactory::new()
        ->forRecipient($editor)
        ->withLinkedElement($page)
        ->create(['title' => 'Ready for review']);

    $stored = Notification::getById($notification->getId());
    expect($stored->getRecipient())
        ->getId()
        ->toBe($editor->getId())
        ->and($stored->getLinkedElement())
        ->getId()
        ->toBe($page->getId())
        ->and($stored->getTitle())
        ->toBe('Ready for review');
});
