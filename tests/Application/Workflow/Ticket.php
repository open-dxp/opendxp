<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\Workflow;

/**
 * A ticket is a subject of a plain Symfony workflow, like the order of a bundle.
 */
final class Ticket
{
    private ?string $state = null;

    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setState(string $state, array $context = []): void
    {
        $this->state = $state;
    }
}
