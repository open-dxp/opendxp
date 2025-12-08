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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\CoreBundle\Command;

use OpenDxp\Console\AbstractCommand;
use OpenDxp\Maintenance\ExecutorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:maintenance',
    description: 'Asynchronous maintenance jobs of opendxp (needs to be set up as cron job)',
    aliases: ['maintenance']
)]
class MaintenanceCommand extends AbstractCommand
{
    public function __construct(private readonly ExecutorInterface $maintenanceExecutor, private readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $description = 'Asynchronous maintenance jobs of opendxp (needs to be set up as cron job)';

        $help = $description.'. Valid jobs are: '."\n\n";
        $help .= '  <comment>*</comment> any bundle class name handling maintenance (e.g. <comment>OpenDxpApplicationLoggerBundle</comment>)'."\n";

        foreach ($this->maintenanceExecutor->getTaskNames() as $taskName) {
            $help .= '  <comment>*</comment> '.$taskName."\n";
        }

        $this
            ->addOption(
                'job',
                'j',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Call just a specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            )
            ->addOption(
                'excludedJobs',
                'J',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $validJobs = $this->getArrayOptionValue($input, 'job');
        $excludedJobs = $this->getArrayOptionValue($input, 'excludedJobs');

        $this->maintenanceExecutor->executeMaintenance(
            $validJobs,
            $excludedJobs
        );

        $this->logger->info('All maintenance-jobs finished!');

        return 0;
    }

    /**
     * Get an array option value, but still support the value being comma-separated for backwards compatibility
     */
    private function getArrayOptionValue(InputInterface $input, string $name): array
    {
        $value = $input->getOption($name);
        $result = [];

        if (!empty($value)) {
            foreach ($value as $val) {
                foreach (explode(',', $val) as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $result[] = $part;
                    }
                }
            }
        }

        return array_unique($result);
    }
}
