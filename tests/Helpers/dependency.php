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

use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\ElementInterface;

function referencing(ElementInterface $source, Asset ...$targets): ElementInterface
{
    foreach (array_values($targets) as $index => $target) {
        $source->setProperty(
            'related' . $index,
            'asset',
            $target,
        );
    }

    $source->save();

    return $source;
}

/**
 * @param list<array<string, mixed>> $dependencies
 *
 * @return list<int>
 */
function dependencyIds(array $dependencies): array
{
    return array_map(
        static fn (array $dependency): int => (int) $dependency['id'],
        $dependencies,
    );
}
