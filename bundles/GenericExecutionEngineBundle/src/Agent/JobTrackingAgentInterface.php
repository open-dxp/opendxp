<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Agent;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;
use Throwable;

interface JobTrackingAgentInterface
{
    /**
     * @param callable(JobProgress): void $callback
     *
     * @throws ExecutionContextNotDefinedException
     * @throws Throwable the exception of the callback
     */
    public function trackJob(
        string $jobName,
        callable $callback,
        ?int $ownerId = null,
        string $executionContext = 'default',
    ): void;
}
