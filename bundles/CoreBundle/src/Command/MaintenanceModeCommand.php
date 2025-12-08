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

use Exception;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Tool\MaintenanceModeHelperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:maintenance-mode',
    description: 'Enable or disable maintenance mode'
)]
class MaintenanceModeCommand extends AbstractCommand
{
    public function __construct(protected MaintenanceModeHelperInterface $maintenanceModeHelper)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Enable maintenance mode (default)')
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Disable maintenance mode')
        ;
    }

    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $input->setOption('ignore-maintenance-mode', true);
        parent::initialize($input, $output);
    }

    /**
     *
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Default behavior is 'enable'
        $disable = ($input->getOption('disable') ?? false);

        if ($disable) {
            $this->maintenanceModeHelper->deactivate();
            if ($output->isVerbose()) {
                $output->writeln('Maintenance mode has been disabled');
            }
        } else {
            $this->maintenanceModeHelper->activate('command-line-dummy-session-id');
            if ($output->isVerbose()) {
                $output->writeln('Maintenance mode is now enabled');
                $output->writeln('You can run commands only with the --ignore-maintenance-mode option');
            }
        }

        return 0;
    }
}
