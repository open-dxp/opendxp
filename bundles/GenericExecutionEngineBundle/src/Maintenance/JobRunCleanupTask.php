<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Maintenance;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgent;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Maintenance\TaskInterface;
use RuntimeException;
use Symfony\Component\Clock\Clock;

/**
 * @internal
 */
final readonly class JobRunCleanupTask implements TaskInterface
{
    private const int DISPOSABLE_LIFETIME = 86400;

    public function __construct(
        private JobExecutionAgent $jobExecutionAgent,
        private JobRunRepository $jobRunRepository,
        private ExecutionContextInterface $executionContext,
        private ?int $deleteJobRunsOlderThan = null,
        private ?int $failInactiveJobRunsAfter = null,
    ) {
    }

    public function execute(): void
    {
        $now = Clock::get()->now()->getTimestamp();

        if ($this->failInactiveJobRunsAfter !== null) {
            $inactiveSince = $now - $this->failInactiveJobRunsAfter;
            foreach ($this->jobRunRepository->getRunningIdsInactiveSince($inactiveSince) as $jobRunId) {
                $this->jobExecutionAgent->failJobRun($jobRunId, new RuntimeException(sprintf(
                    'The job run showed no activity for %d seconds.',
                    $this->failInactiveJobRunsAfter,
                )));
            }
        }

        $disposableContexts = $this->executionContext->getDisposableContexts();
        if ($disposableContexts !== []) {
            $endedBefore = $now - self::DISPOSABLE_LIFETIME;
            foreach ($this->jobRunRepository->getIdsEndedBefore($endedBefore, $disposableContexts) as $jobRunId) {
                $this->jobExecutionAgent->deleteJobRun($jobRunId);
            }
        }

        if ($this->deleteJobRunsOlderThan !== null) {
            $endedBefore = $now - $this->deleteJobRunsOlderThan;
            foreach ($this->jobRunRepository->getIdsEndedBefore($endedBefore) as $jobRunId) {
                $this->jobExecutionAgent->deleteJobRun($jobRunId);
            }
        }
    }
}
