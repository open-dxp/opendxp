<?php

declare(strict_types = 1);

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

namespace OpenDxp\ValueObject\Collection;

use ValueError;

final readonly class ArrayOfPositiveIntegers
{
    /**
     * @throws ValueError
     */
    public function __construct(private array $value)
    {
        $this->validate();
    }

    /**
     * @throws ValueError
     */
    public function __wakeup(): void
    {
        $this->validate();
    }

    private function validate(): void
    {
        foreach ($this->value as $value) {
            if (!is_int($value)) {
                throw new ValueError(
                    sprintf(
                        'Provided array must contain only integer values. (%s given)',
                        gettype($value)
                    ),
                );
            }

            if ($value <= 0) {
                throw new ValueError(
                    sprintf(
                        'Provided integer must be positive. (%s given)',
                        $value
                    ),
                );
            }
        }
    }

    /**
     * @return int[]
     */
    public function getValue(): array
    {
        return $this->value;
    }
}
