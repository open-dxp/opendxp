<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Value;

/**
 * The backend search answers with one page of paths and the total of all results.
 */
final readonly class SearchAnswer
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        public array $paths,
        public int $total,
    ) {
    }
}
