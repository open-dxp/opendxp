<?php

declare(strict_types=1);

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
