<?php

declare(strict_types=1);

namespace OpenDxp\Test;

use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Registry;

final readonly class Workflows
{
    public function __construct(private Registry $registry)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function apply(object $subject, string $workflowName, string $transition, array $context = []): Marking
    {
        return $this->registry
            ->get($subject, $workflowName)
            ->apply($subject, $transition, $context);
    }

    public function can(object $subject, string $workflowName, string $transition): bool
    {
        return $this->registry
            ->get($subject, $workflowName)
            ->can($subject, $transition);
    }
}
