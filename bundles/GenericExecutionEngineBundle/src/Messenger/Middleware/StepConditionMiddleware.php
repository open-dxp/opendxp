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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Middleware;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * @internal
 */
final readonly class StepConditionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private JobRunExtractorInterface $jobRunExtractor,
        private LoggerInterface $genericExecutionEngineLogger,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof GenericExecutionEngineMessageInterface) {
            if ($this->jobRunExtractor->checkCondition($message)) {
                return $stack->next()->handle($envelope, $stack);
            }

            $this->logToJobRun(
                $message,
            );

            $this->jobExecutionAgent->continueJobMessageExecution($message);

            return $envelope;
        }

        return $stack->next()->handle($envelope, $stack);
    }

    private function logToJobRun(
        GenericExecutionEngineMessageInterface $message
    ): void {
        $jobRun = $this->jobRunExtractor->getJobRun($message);
        $jobName = $jobRun->getJob()?->getName();
        $stepId = $message->getCurrentJobStep();
        $stepName = $jobRun->getJob()?->getSteps()[$stepId]->getName();
        $params['%jobName%'] = $jobName;
        $params['%stepId%'] = $stepId;
        $params['%stepName%'] = $stepName;

        $this->jobRunExtractor->logMessageToJobRun(
            $jobRun,
            'gee_middleware_step_condition_not_met',
            $params
        );

        $this->genericExecutionEngineLogger->info(
            "[JobRun {$jobRun->getId()}]:
            Skipping step $stepName with id $stepId of Job '$jobName', job condition not met."
        );
    }
}
