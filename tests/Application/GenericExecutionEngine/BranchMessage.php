<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\JobRunMessageInterface;

/**
 * Starts a branch of work. A branch with depth dispatches two branches one level deeper.
 */
final readonly class BranchMessage implements JobRunMessageInterface
{
    public function __construct(
        public ?int $jobRunId,
        public string $path,
        public int $depth,
    ) {
    }

    public function getJobRunId(): ?int
    {
        return $this->jobRunId;
    }
}
