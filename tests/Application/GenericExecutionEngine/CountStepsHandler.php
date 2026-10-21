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

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobProgress;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Handler\AbstractAutomationActionHandler;
use Symfony\Component\Clock\Clock;

/**
 * Counts the steps of the step configuration. After each step it notes the saved progress in the job run context.
 *
 * The step configuration sets:
 * - `steps`: the number of steps
 * - `secondsPerStep`: the time of each step
 * - `cancelAt`: the step to cancel the job run before it advances. One after the last cancels before the result.
 */
final class CountStepsHandler extends AbstractAutomationActionHandler
{
    public function __construct(private readonly JobExecutionAgentInterface $jobExecutionAgent)
    {
        parent::__construct();
    }

    public function __invoke(CountStepsMessage $message, JobProgress $progress): void
    {
        $configuration = $this->getCurrentJobStepConfig($message);
        $progress->setTotal($configuration['steps']);

        for ($step = 1; $step <= $configuration['steps']; $step++) {
            $this->cancelAt($message, $step);
            Clock::get()->sleep($configuration['secondsPerStep']);
            $progress->advance();

            $jobRun = $this->getJobRun($message, forceReload: true);
            $saved = [...$jobRun->getContext()['saved'] ?? [], $jobRun->getProgress()];
            $this->updateJobRunContext($jobRun, 'saved', $saved);
        }

        $this->cancelAt($message, $configuration['steps'] + 1);
        $progress->setResult('Counted every step.');
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired('steps');
        $this->stepConfiguration->setDefaults([
            'secondsPerStep' => 1,
            'cancelAt' => null,
        ]);
    }

    private function cancelAt(CountStepsMessage $message, int $step): void
    {
        if ($step === $this->getCurrentJobStepConfig($message)['cancelAt']) {
            $this->jobExecutionAgent->cancelJobRun($message->getJobRunId());
        }
    }
}
