<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Workflow;

use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Workflows;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Application\Workflow\Ticket;

it('applies a transition of a Symfony workflow', function () {
    $ticket = new Ticket();

    Container::get(Workflows::class)->apply($ticket, 'test_ticket', 'close');

    expect($ticket->getState())->toBe('closed');
});

it('applies a transition of an OpenDXP workflow to an element', function () {
    $page = DocumentPageFactory::createOne();

    Container::get(Workflows::class)->apply($page, 'test_review', 'submit');

    $canPublish = Container::get(Workflows::class)->can(Elements::refresh($page), 'test_review', 'publish');
    expect($canPublish)->toBeTrue();
});

it('tells that a transition cannot apply in the current place', function () {
    $page = DocumentPageFactory::createOne();

    $canPublish = Container::get(Workflows::class)->can($page, 'test_review', 'publish');

    expect($canPublish)->toBeFalse();
});
