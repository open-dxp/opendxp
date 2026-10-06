<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

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
