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

namespace OpenDxp\Workflow;

class WorkflowConfig
{
    public function __construct(private readonly string $name, private array $workflowConfigArray)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->workflowConfigArray['label'] ?? $this->name;
    }

    public function getPriority(): int
    {
        return $this->workflowConfigArray['priority'];
    }

    public function getType(): string
    {
        return $this->workflowConfigArray['type'];
    }

    public function getWorkflowConfigArray(): array
    {
        return $this->workflowConfigArray;
    }
}
