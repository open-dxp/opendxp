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

/**
 * One kind of relation, and the name it carries in each of the places the LazyLoading class offers.
 */
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
     * Builds what a field of this kind takes. An advanced relation is wrapped in metadata that names
     * the field it belongs to, and a single relation is one target instead of a list.
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
     * Counts what the field handed back. A single relation hands back the one target it points at
     * instead of a list.
     */
    public function counted(mixed $loaded): int
    {
        return $this->single ? (int) ($loaded !== null) : count($loaded);
    }
}
