<?php

declare(strict_types=1);

use OpenDxp\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @param array<string, string> $input
 */
function runConsole(array $input): string
{
    $application = new Application(OpenDxp::getKernel());
    $application->setAutoExit(false);
    $output = new BufferedOutput();
    $application->run(new ArrayInput($input), $output);

    return $output->fetch();
}
