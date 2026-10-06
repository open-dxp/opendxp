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

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Element\ElementInterface;

/**
 * Loads the element again the way the next request would, past every cache of this process.
 *
 * @template T of ElementInterface
 *
 * @param T $element
 *
 * @return T
 */
function reloaded(ElementInterface $element): ElementInterface
{
    RuntimeCache::clear();

    return $element::getById(
        $element->getId(),
        ['force' => true],
    );
}

/**
 * @param array<ElementInterface> $elements
 *
 * @return list<int>
 */
function elementIds(array $elements): array
{
    return array_map(
        static fn (ElementInterface $element): int => $element->getId(),
        array_values($elements),
    );
}
