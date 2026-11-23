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

namespace OpenDxp\Tests\Application\Workflow;

/**
 * A ticket is a subject of a plain Symfony workflow, like the order of a bundle.
 */
final class Ticket
{
    private ?string $state = null;

    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setState(string $state, array $context = []): void
    {
        $this->state = $state;
    }
}
