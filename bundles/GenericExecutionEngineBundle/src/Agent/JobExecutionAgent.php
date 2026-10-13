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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Agent;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use LogicException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\Job;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use OpenDxp\Model\Element\ElementDescriptor;
use OpenDxp\Model\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Throwable;

/**
 * @internal
 */
final class JobExecutionAgent implements JobExecutionAgentInterface
{
    private const string LOG_JOB_RUN_ID_KEY = '%job_run_id%';

    private const string LOG_JOB_RUN_NAME_KEY = '%job_run_name%';

    private const string TRANSPORT = 'opendxp_generic_execution_engine';

    private bool $isDev;

    public function __construct(
        string $environment,
        private readonly string $errorHandlingMode,
        private readonly ExecutionContextInterface $executionContext,
        private readonly JobRunRepository $jobRunRepository,
        private readonly JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository,
        private readonly JobRunExtractorInterface $jobRunExtractor,
        private readonly JobRunArtifactStorage $jobRunArtifactStorage,
        private readonly PendingOperations $pendingOperations,
        private readonly Connection $db,
        private readonly LoggerInterface $genericExecutionEngineLogger,
        private readonly MessageBusInterface $executionEngineBus,
        private readonly SendersLocatorInterface $sendersLocator,
    ) {
        $this->isDev = $environment === 'dev';
    }

    /**
     * @throws Exception
     */
    public function startJobExecution(
        Job $job,
        ?int $ownerId,
        string $executionContext = 'default'
    ): JobRun {
        $jobRun = $this->jobRunRepository->createFromJob($job, $ownerId);
        $jobRun->setJobName($job->getName());
        $jobRun->setExecutionContext($executionContext);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setTotalElements(count($job->getSelectedElements()));
        $jobRun->setPendingOperations(1);

        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_started',
            $this->getLogParams($jobRun)
        );

        $this->dispatchStepMessages($jobRun);

        return $jobRun;
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public function startJobRun(string $jobName, ?int $ownerId, string $executionContext): JobRun
    {
        $jobRun = new JobRun($ownerId);
        $jobRun->setJobName($jobName);
        $jobRun->setExecutionContext($executionContext);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setPendingOperations(1);
        $this->jobRunRepository->update($jobRun);

        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_started',
            $this->getLogParams($jobRun)
        );

        return $jobRun;
    }

    /**
     * @deprecated since 1.5 and will be removed in 2.0
     */
    public function continueJobMessageExecution(
        GenericExecutionEngineMessageInterface $message,
        ?Throwable $throwable = null
    ): void {
        trigger_deprecation(
            'open-dxp/opendxp',
            '1.5',
            'Calling "%s()" is deprecated. The engine counts the messages of a job run itself.',
            __METHOD__,
        );
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public function continueJobRun(int $jobRunId): void
    {
        $nextStep = $this->db->transactional(function () use ($jobRunId): ?JobRun {
            $row = $this->db->fetchAssociative(
                sprintf(
                    'SELECT state, pendingOperations FROM %s WHERE id = ? FOR UPDATE',
                    TableConstants::JOB_RUN_TABLE,
                ),
                [$jobRunId],
            );

            if ($row === false
                || $row['state'] !== JobRunStates::RUNNING->value
                || (int) $row['pendingOperations'] !== 0
            ) {
                return null;
            }

            $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, forceReload: true);
            if (!$this->hasNextStep($jobRun)) {
                $this->setCompletionState($jobRun);

                return null;
            }

            $jobRun->setCurrentStep($jobRun->getCurrentStep() + 1);
            $jobRun->setProcessedElementsForStep(0);
            $jobRun->setPendingOperations(1);
            $this->jobRunRepository->update($jobRun);

            return $jobRun;
        });

        if ($nextStep instanceof JobRun) {
            $this->dispatchStepMessages($nextStep);
        }
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public function addMessageError(int $jobRunId, ?int $elementId, Throwable $throwable): void
    {
        $jobRun = $this->findJobRun($jobRunId);
        if (!$jobRun instanceof JobRun) {
            return;
        }

        $this->addError($jobRun, $elementId, $throwable);

        if ($jobRun->getState() === JobRunStates::RUNNING
            && $this->getErrorHandlingMode($jobRun) === ErrorHandlingMode::STOP_ON_FIRST_ERROR
        ) {
            $this->setFailedState($jobRun, 'gee_job_failed', $this->getLogParams($jobRun));
        }
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public function failJobRun(int $jobRunId, Throwable $throwable): void
    {
        $jobRun = $this->findJobRun($jobRunId);
        if (!$jobRun instanceof JobRun || $jobRun->getState() !== JobRunStates::RUNNING) {
            return;
        }

        $this->addError($jobRun, null, $throwable);
        $this->setFailedState($jobRun, 'gee_job_failed', $this->getLogParams($jobRun));
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public function addDownloadedArtifact(int $jobRunId, string $filename): void
    {
        $jobRun = $this->findJobRun($jobRunId);
        $result = $jobRun?->getResult();
        if ($result === null || !$result->hasArtifact($filename)) {
            return;
        }

        $result = $result->withDownloadedArtifact($filename);
        $jobRun->setResult($result);
        $this->jobRunRepository->update($jobRun);

        if ($jobRun->getState() !== JobRunStates::RUNNING
            && $result->isEveryArtifactDownloaded()
            && $this->executionContext->isDisposable($jobRun->getExecutionContext())
        ) {
            $this->jobRunRepository->delete($jobRun);
        }
    }

    public function isInteractionAllowed(int $jobRunId, int $ownerId): bool
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);

        return $ownerId === $jobRun->getOwnerId();
    }

    /**
     * @throws Exception
     */
    public function cancelJobRun(int $jobRunId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);
        if ($jobRun->getState() !== JobRunStates::RUNNING) {
            return;
        }

        $this->genericExecutionEngineLogger->info(sprintf('[JobRun %d]: JobRun cancelled.', $jobRun->getId()));
        $jobRun->setState(JobRunStates::CANCELLED);
        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_cancelled',
            $this->getLogParams($jobRun)
        );

        $this->deleteDisposableJobRun($jobRun);
    }

    /**
     * @throws Exception
     */
    public function rerunJobRun(int $jobRunId, ?int $ownerId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId);
        $this->startJobExecution($jobRun->getJob(), $ownerId, $jobRun->getExecutionContext());
    }

    /**
     * @throws LogicException when the job run still runs
     */
    public function deleteJobRun(int $jobRunId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);
        if ($jobRun->getState() === JobRunStates::RUNNING) {
            throw new LogicException(sprintf('The job run %d still runs. Cancel it before deleting it.', $jobRunId));
        }

        $this->jobRunRepository->delete($jobRun);
        $this->genericExecutionEngineLogger->info(sprintf('[JobRun %d]: JobRun deleted.', $jobRunId));
    }

    public function isRunning(int $jobRunId): bool
    {
        return $this->jobRunRepository->findState($jobRunId) === JobRunStates::RUNNING;
    }

    private function findJobRun(int $jobRunId): ?JobRun
    {
        try {
            return $this->jobRunRepository->getJobRunById($jobRunId, forceReload: true);
        } catch (NotFoundException) {
            return null;
        }
    }

    private function hasNextStep(JobRun $jobRun): bool
    {
        $job = $jobRun->getJob();

        return $job instanceof Job
            && $jobRun->getCurrentStep() !== null
            && $jobRun->getCurrentStep() + 1 < count($job->getSteps());
    }

    private function getSelectionProcessingMode(JobRun $jobRun): ?SelectionProcessingMode
    {
        $currentStep = $jobRun->getCurrentStep();
        $steps = $jobRun->getJob()?->getSteps() ?? [];

        return $currentStep === null ? null : ($steps[$currentStep] ?? null)?->getSelectionProcessingMode();
    }

    private function getErrorHandlingMode(JobRun $jobRun): ErrorHandlingMode
    {
        // A step with one message for all elements cannot continue without that message.
        if ($this->getSelectionProcessingMode($jobRun) === SelectionProcessingMode::ONCE) {
            return ErrorHandlingMode::STOP_ON_FIRST_ERROR;
        }

        $errorHandling = $this->executionContext->getErrorHandlingFromContext($jobRun->getExecutionContext());

        return ErrorHandlingMode::from($errorHandling ?? $this->errorHandlingMode);
    }

    /**
     * @throws Exception
     */
    private function addError(JobRun $jobRun, ?int $elementId, Throwable $throwable): void
    {
        $this->genericExecutionEngineLogger->error(sprintf('[JobRun %d]: %s', $jobRun->getId(), $throwable));

        $errorMessage = $throwable->getMessage();
        if ($this->isDev) {
            $errorMessage .= ' Stack trace: ' . str_replace("\n", '', $throwable->getTraceAsString());
        }

        $this->jobRunErrorLogRepository->createFromJobRun($jobRun, $elementId, $errorMessage);
    }

    /**
     * @throws Exception
     */
    private function setCompletionState(JobRun $jobRun): void
    {
        $errors = $this->jobRunErrorLogRepository->getTotalCountByJobRunId($jobRun->getId());

        if ($errors === 0) {
            $jobRun->setCurrentMessage(null);
            $jobRun->setState(JobRunStates::FINISHED);
            $message = 'gee_job_finished';
        } elseif ($jobRun->getSucceededMessages() === 0) {
            $jobRun->setState(JobRunStates::FAILED);
            $message = 'gee_job_failed';
        } else {
            $jobRun->setState(JobRunStates::FINISHED_WITH_ERRORS);
            $message = 'gee_job_finished_with_errors';
        }

        $jobRun->setCurrentStep(null);
        $this->jobRunRepository->updateLogLocalizedWithDomain($jobRun, $message, $this->getLogParams($jobRun));
        $this->genericExecutionEngineLogger->info(sprintf('[JobRun %d]: JobRun ended.', $jobRun->getId()));

        $this->deleteDisposableJobRun($jobRun);
    }

    /**
     * @throws Exception
     */
    private function setFailedState(JobRun $jobRun, string $message, array $params): void
    {
        $jobRun->setState(JobRunStates::FAILED);
        $this->jobRunRepository->updateLogLocalizedWithDomain($jobRun, $message, $params);
        $this->genericExecutionEngineLogger->info(sprintf('[JobRun %d]: JobRun failed.', $jobRun->getId()));

        $this->deleteDisposableJobRun($jobRun);
    }

    private function deleteDisposableJobRun(JobRun $jobRun): void
    {
        if (!$this->executionContext->isDisposable($jobRun->getExecutionContext())) {
            return;
        }

        match ($jobRun->getState()) {
            JobRunStates::CANCELLED => $this->jobRunRepository->delete($jobRun),
            JobRunStates::FAILED => $this->jobRunArtifactStorage->delete($jobRun->getId()),
            JobRunStates::FINISHED, JobRunStates::FINISHED_WITH_ERRORS => $jobRun->getOwnerId() === null
                ? $this->jobRunRepository->delete($jobRun)
                : null,
            default => null,
        };
    }

    /**
     * @throws Exception
     */
    private function dispatchStepMessages(JobRun $jobRun): void
    {
        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            $this->setFailedState($jobRun, 'gee_error_no_job_definition', $this->getLogParams($jobRun));

            return;
        }

        $jobStep = $job->getSteps()[$jobRun->getCurrentStep()];
        $messageClass = $jobStep->getMessageFQCN();
        if (!class_exists($messageClass)) {
            $this->setFailedState(
                $jobRun,
                'gee_error_missing_message_implementation',
                ['%message%' => $messageClass, ...$this->getLogParams($jobRun)],
            );

            return;
        }

        if (!$this->jobRunExtractor->checkStepCondition($jobRun, $jobStep)) {
            $this->jobRunRepository->updateLogLocalizedWithDomain($jobRun, 'gee_middleware_step_condition_not_met', [
                '%jobName%' => $job->getName(),
                '%stepId%' => $jobRun->getCurrentStep(),
                '%stepName%' => $jobStep->getName(),
            ]);
        } else {
            $this->dispatchMessagesOfStep($jobRun, $job->getName(), $messageClass, $job->getSelectedElements());
        }

        if ($this->pendingOperations->remove($jobRun->getId())) {
            $this->continueJobRun($jobRun->getId());
        }
    }

    /**
     * @param list<ElementDescriptor> $selectedElements
     */
    private function dispatchMessagesOfStep(
        JobRun $jobRun,
        string $jobName,
        string $messageClass,
        array $selectedElements,
    ): void {
        $this->genericExecutionEngineLogger->info(sprintf(
            "[JobRun %d]: Starting job step %d of Job '%s'.",
            $jobRun->getId(),
            $jobRun->getCurrentStep(),
            $jobName,
        ));

        if ($selectedElements === [] || $this->getSelectionProcessingMode($jobRun) === SelectionProcessingMode::ONCE) {
            $this->dispatchMessage(new $messageClass($jobRun->getId(), $jobRun->getCurrentStep()));

            return;
        }

        foreach ($selectedElements as $selectedElement) {
            $this->dispatchMessage(new $messageClass($jobRun->getId(), $jobRun->getCurrentStep(), $selectedElement));
        }
    }

    private function dispatchMessage(GenericExecutionEngineMessageInterface $message): void
    {
        $envelope = Envelope::wrap($message);

        // A message without routing would run at once in the request instead of in the background.
        if (iterator_count($this->sendersLocator->getSenders($envelope)) === 0) {
            $envelope = $envelope->with(new TransportNamesStamp([self::TRANSPORT]));
        }

        $this->executionEngineBus->dispatch($envelope);
    }

    private function getLogParams(JobRun $jobRun): array
    {
        return [
            self::LOG_JOB_RUN_ID_KEY => $jobRun->getId(),
            self::LOG_JOB_RUN_NAME_KEY => $jobRun->getJobName(),
        ];
    }
}
