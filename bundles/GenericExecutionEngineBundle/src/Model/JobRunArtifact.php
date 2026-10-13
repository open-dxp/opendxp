<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Model;

use InvalidArgumentException;

final readonly class JobRunArtifact
{
    /**
     * @param resource $stream
     */
    public function __construct(
        public string $filename,
        public mixed $stream,
    ) {
        $plainFilename = basename(str_replace('\\', '/', $filename));
        if ($filename === '' || $filename !== $plainFilename || str_starts_with($filename, '.')) {
            throw new InvalidArgumentException(sprintf('The artifact name "%s" is not a plain file name.', $filename));
        }

        if (!is_resource($stream)) {
            throw new InvalidArgumentException(sprintf('The artifact "%s" has no readable stream.', $filename));
        }
    }
}
