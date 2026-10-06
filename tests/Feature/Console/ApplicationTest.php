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

namespace OpenDxp\Tests\Feature\Console;

use OpenDxp;
use OpenDxp\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @param array<string, string> $parameters
 */
function runConsole(array $parameters): string
{
    $application = new Application(OpenDxp::getKernel());
    $application->setAutoExit(false);
    $input = new ArrayInput($parameters);
    $output = new BufferedOutput();

    $application->run($input, $output);

    return $output->fetch();
}

it('offers the option prefix on the migration commands', function (string $command) {
    $application = new Application(OpenDxp::getKernel());

    $definition = $application->find($command)->getDefinition();

    expect($definition->hasOption('prefix'))->toBeTrue();
})->with([
    'migrate' => ['doctrine:migrations:migrate'],
    'status' => ['doctrine:migrations:status'],
    'list' => ['doctrine:migrations:list'],
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
