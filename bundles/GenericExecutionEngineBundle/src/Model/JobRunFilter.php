<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Model;

/**
 * @internal
 */
final readonly class JobRunFilter
{
    public function __construct(
        public ?int $ownerId = null,
        public bool $withoutOwner = false,
        public ?string $jobName = null,
        public ?JobRunStates $state = null,
        public ?int $startedFrom = null,
        public ?int $startedUntil = null,
    ) {
    }
}
