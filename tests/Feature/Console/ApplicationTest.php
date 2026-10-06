<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Console;

use OpenDxp;
use OpenDxp\Console\Application;

it('offers the option prefix on the migration commands', function (string $command) {
    $application = new Application(OpenDxp::getKernel());

    expect($application->find($command)->getDefinition()->hasOption('prefix'))
        ->toBeTrue();
})->with([
    'doctrine:migrations:migrate',
    'doctrine:migrations:status',
    'doctrine:migrations:list',
]);

it('lists only the migrations of the prefix', function () {
    $output = runConsole([
        'command' => 'doctrine:migrations:list',
        '--prefix' => 'OpenDxp\Bundle\CoreBundle\Migrations',
    ]);

    preg_match_all('/([A-Za-z\\\\]+\\\\Migrations\\\\Version\d+)/', $output, $versions);

    expect($versions[1])
        ->not->toBeEmpty()
        ->each->toStartWith('OpenDxp\Bundle\CoreBundle\Migrations\\');
});
