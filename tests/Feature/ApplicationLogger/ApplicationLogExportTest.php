<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\ApplicationLogger;

use OpenDxp\Bundle\AdminBundle\Test\GridExport\GridExports;
use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use OpenDxp\Db;
use OpenDxp\Test\Factory\UserFactory;

/**
 * @param array<string, mixed> $values
 */
function writeLogEntry(array $values): void
{
    Db::get()->insert(ApplicationLoggerDb::TABLE_NAME, [
        'timestamp' => '2026-10-09 12:00:00',
        'priority' => 'error',
        'message' => 'Something failed',
        ...$values,
    ]);
}

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
});

it('exports the entries that the filters of the grid find', function () {
    writeLogEntry([
        'component' => 'import',
        'message' => 'Import failed',
    ]);
    writeLogEntry([
        'component' => 'export',
        'message' => 'Export failed',
    ]);

    $file = GridExports::export($this->admin, 'application-log', ['component' => 'import']);

    expect($file)
        ->getColumn('Message')
        ->toBe(['Import failed']);
});

it('exports the time of an entry in the timezone of the user', function () {
    writeLogEntry([
        'component' => 'timezone',
        'timestamp' => '2026-10-09 12:00:00',
    ]);

    $file = GridExports::export(
        $this->admin,
        'application-log',
        ['component' => 'timezone'],
        timezone: 'Europe/Zurich',
    );

    expect($file)
        ->getColumn('Timestamp')
        ->toBe(['2026-10-09 14:00:00']);
});

it('exports the type and the id of a related element together, as the grid shows them', function () {
    writeLogEntry([
        'component' => 'related',
        'relatedobject' => 42,
        'relatedobjecttype' => 'document',
    ]);

    $file = GridExports::export($this->admin, 'application-log', ['component' => 'related']);

    expect($file)
        ->getColumn('Related object')
        ->toBe(['document 42']);
});
