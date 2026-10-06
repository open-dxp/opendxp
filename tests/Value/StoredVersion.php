<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Value;

/**
 * A version row together with the data the database adapter writes beside it.
 */
final readonly class StoredVersion
{
    public function __construct(
        public string $storageType,
        public ?int $binaryFileId,
        public ?string $metaData,
        public ?string $binaryData,
    ) {
    }
}
