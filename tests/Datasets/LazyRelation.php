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


namespace OpenDxp\Tests\Datasets;

use OpenDxp\Model\DataObject\Concrete;

final class LazyRelation
{
    public function __construct(
        public readonly string $onTheObject,
        public readonly string $localized,
        public readonly string $inABlock,
        public readonly string $inALazyBlock,
        public readonly string $blockType,
        public readonly int $expected = 5,
        public readonly ?string $metadata = null,
        public readonly bool $single = false,
    ) {
    }

    /**
     * An advanced relation wraps each target in metadata that holds the name of its field. A single relation takes
     * one target instead of a list.
     */
    public function value(string $field, array $targets): mixed
    {
        if ($this->single) {
            return $targets[0];
        }

        if ($this->metadata === null) {
            return $targets;
        }

        return array_map(fn (Concrete $target) => new ($this->metadata)($field, [], $target), $targets);
    }

    /**
     * A single relation hands back one target instead of a list.
     */
    public function counted(mixed $loaded): int
    {
        return $this->single ? (int) ($loaded !== null) : count($loaded);
    }
}
