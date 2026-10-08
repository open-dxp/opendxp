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

namespace OpenDxp\Test\Document;

use OpenDxp\Model\Document\Editable;
use OpenDxp\Model\Document\Editable\Areablock;

final class Areablocks
{
    /**
     * Returns the editables of an areablock that holds the bricks, in the order they are given. The names are relative
     * to the place of the areablock. A brick whose template holds an areablock adds them to its own editables, and
     * OpenDXP names the editables of the inner bricks like "content:1.inner:1.text".
     *
     * @return array<string, Editable>
     */
    public static function filledWith(string $areablock, Brick ...$bricks): array
    {
        $indices = [];
        $editables = [];

        foreach (array_values($bricks) as $position => $brick) {
            $key = (string) ($position + 1);
            $indices[] = [
                'key' => $key,
                'type' => $brick->id(),
                'hidden' => false,
            ];

            foreach ($brick->editables() as $name => $editable) {
                $editables[sprintf('%s:%s.%s', $areablock, $key, $name)] = $editable;
            }
        }

        $block = new Areablock();
        $block->setDataFromEditmode($indices);

        return [
            $areablock => $block,
            ...$editables,
        ];
    }
}
