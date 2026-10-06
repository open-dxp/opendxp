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

namespace OpenDxp\Tests\Value;

use Closure;

/**
 * A relation type has a field of its own on the object, in the localized fields and in two blocks. Its
 * closures turn the targets into the value a field of this type takes and count the targets a field returns.
 */
final readonly class LazyRelation
{
    /**
     * @param Closure(string, list<object>): mixed $value
     * @param Closure(mixed): int $count
     */
    public function __construct(
        public string $onTheObject,
        public string $localized,
        public string $inABlock,
        public string $inALazyBlock,
        public string $blockType,
        public int $expected,
        private Closure $value,
        private Closure $count,
    ) {
    }

    /**
     * @param list<object> $targets
     */
    public function value(string $field, array $targets): mixed
    {
        return ($this->value)($field, $targets);
    }

    public function counted(mixed $loaded): int
    {
        return ($this->count)($loaded);
    }
}
