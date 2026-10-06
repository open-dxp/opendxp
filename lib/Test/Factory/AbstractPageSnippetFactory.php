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

    public function withContentMainDocument(PageSnippet $main): static
    {
        return $this->with(['contentMainDocumentId' => $main->getId()]);
    }

    /**
     * @param array<string, Editable> $editables
     */
    public function withEditables(array $editables): static
    {
        return $this->afterInstantiate(
            static function (PageSnippet $document) use ($editables): void {
                foreach ($editables as $name => $editable) {
                    // Every document a factory creates needs editables of its own.
                    $copy = clone $editable;
                    $copy->setName($name);
                    $document->setEditable($copy);
                }
            },
        );
    }

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
                $nameInAreablock = sprintf('%s:%s.%s', $areablock, $key, $name);
                $editables[$nameInAreablock] = $editable;
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
}
