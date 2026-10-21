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

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

/**
 * Collects what the handlers of a test noted, keyed by a name.
 */
final class Notes
{
    /**
     * @var array<string, mixed>
     */
    private array $notes = [];

    public function add(string $name, mixed $value): void
    {
        $this->notes[$name] = $value;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->notes);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->notes;
    }
}
