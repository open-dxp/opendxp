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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document\Editable;
use OpenDxp\Model\Document\Editable\Areablock;
use OpenDxp\Model\Document\PageSnippet;
use OpenDxp\Test\Document\Brick;

/**
 * @template T of PageSnippet
 *
 * @extends AbstractDocumentFactory<T>
 */
abstract class AbstractPageSnippetFactory extends AbstractDocumentFactory
{
    /**
     * @param class-string $controller
     */
    public function withController(string $controller, string $action): static
    {
        return $this->with(['controller' => sprintf('%s::%s', $controller, $action)]);
    }

    /**
     * @param array<string, Editable> $editables the editables by the name the template gives them
     */
    public function withEditables(array $editables): static
    {
        return $this->afterInstantiate(static function (PageSnippet $document) use ($editables): void {
            foreach ($editables as $name => $editable) {
                // A factory creates many documents, and each one needs editables of its own.
                $copy = clone $editable;
                $copy->setName($name);
                $document->setEditable($copy);
            }
        });
    }

    /**
     * @param string $areablock the name the template gives the areablock
     */
    public function withBricks(string $areablock, Brick ...$bricks): static
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

        return $this->withEditables([
            $areablock => $block,
            ...$editables,
        ]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'missingRequiredEditable' => false,
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()->withNavigationName();
    }
}
