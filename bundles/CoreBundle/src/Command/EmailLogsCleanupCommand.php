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

use DateTime;
use Exception;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Model\Tool\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:email:cleanup',
    description: 'Cleanup email logs',
    aliases: ['email:cleanup']
)]
class EmailLogsCleanupCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'older-than-days',
                'days',
                InputOption::VALUE_REQUIRED,
                'Older than X Days to delete email logs'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daysAgo = $input->getOption('older-than-days');
        if (!isset($daysAgo)) {
            throw new Exception('Missing option "--older-than-days"');
        }

        if (!is_numeric($daysAgo)) {
            throw new Exception('The "--older-than-days" option value should be numeric');
        }

        $date = new DateTime("-{$daysAgo} days");
        $dateTimestamp = $date->getTimestamp();
        $emailLogs = new Email\Log\Listing();
        $emailLogs->setCondition("sentDate < $dateTimestamp");

        foreach ($emailLogs->load() as $emailLog) {
            $emailLog->delete();
        }

        $this->output->writeln('Email logs cleanup done!');

        return 0;
    }
}
