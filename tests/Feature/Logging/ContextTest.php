<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Logging;

use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;
use Symfony\Bridge\Monolog\Processor\ConsoleCommandProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function findLastRecordWith(TestHandler $handler, string $key): ?LogRecord
{
    $records = array_filter(
        $handler->getRecords(),
        fn (LogRecord $record) => isset($record->extra[$key]),
    );

    return $records === [] ? null : end($records);
}

it('names the user and the route of a failed request', function () {
    $handler = new TestHandler();
    Container::get('monolog.logger.request')->pushHandler($handler);
    $admin = UserFactory::new()
        ->admin()
        ->create();

    Browser::actingAs($admin)->get('/admin/document/get-data-by-id?id=999999999');

    $record = findLastRecordWith($handler, 'token');
    expect($record->extra['token']['user_identifier'])
        ->toBe($admin->getName())
        ->and($record->extra['requests'][0]['route'])
        ->toBe('opendxp_admin_document_document_getdatabyid');
});

// The processor keeps the command until the command ends.
it('names the running console command', function () {
    $handler = new TestHandler();
    $logger = Container::get('logger');
    $logger->pushHandler($handler);
    $event = new ConsoleCommandEvent(
        new Command('opendxp:thumbnails:image'),
        new ArrayInput([]),
        new NullOutput(),
    );
    Container::get('event_dispatcher')->dispatch($event, ConsoleEvents::COMMAND);

    $logger->info('Thumbnails generated.');

    expect(findLastRecordWith($handler, 'command')->extra['command']['name'])->toBe('opendxp:thumbnails:image');
})->after(fn () => Container::get(ConsoleCommandProcessor::class)->reset());
