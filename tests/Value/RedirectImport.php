<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Value;

/**
 * The redirect import counts how many lines it created, updated and refused.
 */
final readonly class RedirectImport
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $errored,
    ) {
    }
}
