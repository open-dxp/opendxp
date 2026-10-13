<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Notes the state of its job run for each branch. It dispatches the deeper branches after it returned, the latest
 * moment a job run can count them. Its bus has no JobTrackingMiddleware.
 */
final readonly class BranchHandler
{
    public function __construct(
        private JobRunRepository $jobRunRepository,
        private Notes $notes,
        #[Autowire(service: 'job_run_test.bus')]
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(BranchMessage $message): void
    {
        $this->notes->add($message->path, $this->jobRunRepository->findState($message->jobRunId)?->value);

        if ($message->depth === 0) {
            return;
        }

        foreach (['a', 'b'] as $branch) {
            $this->bus->dispatch(
                new BranchMessage($message->jobRunId, $message->path . $branch, $message->depth - 1),
                [new DispatchAfterCurrentBusStamp()],
            );
        }
    }
}
