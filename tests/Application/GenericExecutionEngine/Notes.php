<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

/**
 * Collects what the handlers of a test noted, keyed by a name.
 */
final class Notes
{
    /**
     * @var array<string, mixed>
     */
    private array $notes = [];

    public function add(string $name, mixed $value): void
    {
        $this->notes[$name] = $value;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->notes);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->notes;
    }
}
