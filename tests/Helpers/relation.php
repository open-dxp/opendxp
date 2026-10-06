<?php

declare(strict_types=1);

use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;

/**
 * Each relation of these tests carries a note in its metadata column `meta`. The notes tell the relations
 * to the same target apart.
 *
 * @param list<ElementMetadata|ObjectMetadata> $relations
 *
 * @return list<string>
 */
function notesOf(array $relations): array
{
    return array_map(
        static fn (ElementMetadata|ObjectMetadata $relation): string => $relation->getMeta(),
        $relations,
    );
}
