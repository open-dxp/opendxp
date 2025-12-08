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

namespace OpenDxp\ValueObject\String;

use ValueError;

final readonly class Path
{
    /**
     * @throws ValueError
     */
    public function __construct(private string $path)
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
        if (!str_starts_with($this->path, '/')) {
            throw new ValueError('Path must start with a slash.');
        }

        if (str_contains($this->path, '//')) {
            throw new ValueError('Path must not contain consecutive slashes.');
        }
    }

    public function getValue(): string
    {
        return $this->path;
    }

    public function equals(Path $path): bool
    {
        return $this->path === $path->getValue();
    }
}
