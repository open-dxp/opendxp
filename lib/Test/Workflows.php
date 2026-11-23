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

namespace OpenDxp\Test;

use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Registry;

final readonly class Workflows
{
    public function __construct(private Registry $registry)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function apply(object $subject, string $workflowName, string $transition, array $context = []): Marking
    {
        return $this->registry
            ->get($subject, $workflowName)
            ->apply($subject, $transition, $context);
    }

    public function can(object $subject, string $workflowName, string $transition): bool
    {
        return $this->registry
            ->get($subject, $workflowName)
            ->can($subject, $transition);
    }
}
