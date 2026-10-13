<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\JobRunMessageInterface;

/**
 * Fails at its first attempt. Its transport tries it once more.
 */
final readonly class RetryOnceMessage implements JobRunMessageInterface
{
    public function __construct(
        public ?int $jobRunId,
        public string $name,
    ) {
    }

    public function getJobRunId(): ?int
    {
        return $this->jobRunId;
    }
}
