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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Document\Editable;

use Exception;
use JsonException;
use OpenDxp\Model\Document\Editable;

/**
 * @internal
 */
final class EditmodeEditableDefinitionCollector
{
    private bool $stopped = false;

    private array $editableDefinitions = [];

    private array $stash = [];

    /**
     *
     * @throws Exception
     */
    public function add(Editable $editable): void
    {
        if ($this->stopped) {
            return;
        }

        $this->editableDefinitions[$editable->getName()] = $editable->getEditmodeDefinition();
    }

    public function remove(Editable $editable): void
    {
        if ($this->stopped) {
            return;
        }

        if (isset($this->editableDefinitions[$editable->getName()])) {
            unset($this->editableDefinitions[$editable->getName()]);
        }
    }

    public function start(): void
    {
        $this->stopped = false;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function stashPush(): void
    {
        $this->stash[] = $this->editableDefinitions;
        $this->editableDefinitions = [];
    }

    public function stashPull(): void
    {
        $this->editableDefinitions = array_pop($this->stash);
    }

    private function clearConfig(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->clearConfig($item);
            }
        } elseif (!is_scalar($value)) {
            $value = null;
        }

        return $value;
    }

    public function getDefinitions(): array
    {
        $configs = [];
        foreach ($this->editableDefinitions as $definition) {
            $configs[] = $this->clearConfig($definition);
        }

        return $configs;
    }

    private function getJson(): string
    {
        return json_encode($this->getDefinitions(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     *
     * @throws JsonException
     */
    public function getHtml(): string
    {
        return '
            <script>
                var editableDefinitions = ' . $this->getJson() . ';
            </script>
        ';
    }
}
