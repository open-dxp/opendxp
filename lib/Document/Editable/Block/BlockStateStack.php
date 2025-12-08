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

namespace OpenDxp\Document\Editable\Block;

use Countable;
use JsonSerializable;
use LogicException;
use RuntimeException;

/**
 * @internal
 *
 * Handles block state (current block level, current block index)
 */
final class BlockStateStack implements Countable, JsonSerializable
{
    /**
     * @var BlockState[]
     */
    private array $states = [];

    public function __construct()
    {
        // we need to make sure there's a default state on the stack
        $this->push();
    }

    /**
     * Adds a new state to the stack
     *
     */
    public function push(?BlockState $blockState = null): void
    {
        if (!$blockState instanceof \OpenDxp\Document\Editable\Block\BlockState) {
            $blockState = new BlockState();
        }

        $this->states[] = $blockState;
    }

    /**
     * Removes current state from the stack
     *
     */
    public function pop(): BlockState
    {
        if (count($this->states) <= 1) {
            throw new LogicException('Can\'t pop the last state off the stack');
        }

        return array_pop($this->states);
    }

    /**
     * Returns current state
     *
     */
    public function getCurrentState(): BlockState
    {
        if ($this->states === []) {
            // this should never happen
            throw new RuntimeException('State stack is empty');
        }

        return array_slice($this->states, -1)[0];
    }

    public function count(): int
    {
        return count($this->states);
    }

    public function jsonSerialize(): array
    {
        return $this->states;
    }

    public function loadArray(array $array): void
    {
        $this->states = [];

        foreach ($array as $blockStateData) {
            $blockState = new BlockState();

            foreach ($blockStateData['blocks'] as $blockData) {
                $blockState->pushBlock(new BlockName($blockData['name'], $blockData['realName']));
            }

            foreach ($blockStateData['indexes'] as $indexData) {
                $blockState->pushIndex($indexData);
            }

            $this->states[] = $blockState;
        }
    }
}
