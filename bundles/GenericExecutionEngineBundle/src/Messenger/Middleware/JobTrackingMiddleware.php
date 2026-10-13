<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Middleware;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobProgress;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\JobRunStoppedException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\JobRunMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandlerArgumentsStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final readonly class JobTrackingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JobRunRepository $jobRunRepository,
        private JobRunArtifactStorage $jobRunArtifactStorage,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $jobRunId = $message instanceof JobRunMessageInterface ? $message->getJobRunId() : null;
        if ($jobRunId === null || $envelope->last(ReceivedStamp::class) === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        if ($this->jobRunRepository->findState($jobRunId) !== JobRunStates::RUNNING) {
            return $envelope;
        }

        $progress = new JobProgress($jobRunId, $this->jobRunRepository, $this->jobRunArtifactStorage);
        $arguments = $envelope->last(HandlerArgumentsStamp::class)?->getAdditionalArguments() ?? [];

        try {
            return $stack->next()->handle(
                $envelope->with(new HandlerArgumentsStamp([...$arguments, $progress])),
                $stack,
            );
        } catch (HandlerFailedException $exception) {
            if (!$this->isStopped($exception)) {
                throw $exception;
            }

            return $envelope;
        } finally {
            $this->saveProgress($progress);
        }
    }

    private function isStopped(HandlerFailedException $exception): bool
    {
        return count($exception->getWrappedExceptions(JobRunStoppedException::class))
            === count($exception->getWrappedExceptions());
    }

    private function saveProgress(JobProgress $progress): void
    {
        try {
            $progress->save();
        } catch (JobRunStoppedException) {
        }
    }
}
