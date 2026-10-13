<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Agent;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\JobRunStoppedException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Installer;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use Throwable;

/**
 * @internal
 */
final readonly class JobTrackingAgent implements JobTrackingAgentInterface
{
    public function __construct(
        private JobExecutionAgent $jobExecutionAgent,
        private JobRunRepository $jobRunRepository,
        private JobRunArtifactStorage $jobRunArtifactStorage,
        private PendingOperations $pendingOperations,
        private ExecutionContextInterface $executionContext,
        private Installer $installer,
    ) {
    }

    public function trackJob(
        string $jobName,
        callable $callback,
        ?int $ownerId = null,
        string $executionContext = 'default',
    ): void {
        if (!$this->executionContext->hasContext($executionContext)) {
            throw new ExecutionContextNotDefinedException(
                sprintf('Execution context "%s" is not defined.', $executionContext),
            );
        }

        if (!$this->installer->isInstalled()) {
            $callback(new JobProgress(null, $this->jobRunRepository, $this->jobRunArtifactStorage));

            return;
        }

        $jobRunId = $this->jobExecutionAgent->startJobRun($jobName, $ownerId, $executionContext)->getId();
        $progress = new JobProgress($jobRunId, $this->jobRunRepository, $this->jobRunArtifactStorage);

        try {
            $callback($progress);
            $progress->save();
        } catch (JobRunStoppedException) {
        } catch (Throwable $throwable) {
            $this->saveProgress($progress);
            $this->jobExecutionAgent->failJobRun($jobRunId, $throwable);

            throw $throwable;
        } finally {
            if ($this->pendingOperations->remove($jobRunId)) {
                $this->jobExecutionAgent->continueJobRun($jobRunId);
            }
        }
    }

    /**
     * Keeps the state of a job run that someone cancelled in the meantime.
     */
    private function saveProgress(JobProgress $progress): void
    {
        try {
            $progress->save();
        } catch (JobRunStoppedException) {
        }
    }
}
