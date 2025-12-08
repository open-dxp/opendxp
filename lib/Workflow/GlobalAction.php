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

use OpenDxp\Workflow\Notes\CustomHtmlServiceInterface;
use OpenDxp\Workflow\Notes\NotesAwareInterface;
use OpenDxp\Workflow\Notes\NotesAwareTrait;
use Symfony\Component\Workflow\WorkflowInterface;

class GlobalAction implements NotesAwareInterface
{
    use NotesAwareTrait;

    /**
     * @var array
     */
    private $options;

    public function __construct(private string $name, array $options, private ExpressionService $expressionService, private string $workflowName, ?CustomHtmlServiceInterface $customHtmlService = null)
    {
        $this->options = $options;
        if ($customHtmlService instanceof CustomHtmlServiceInterface) {
            $this->setCustomHtmlService($customHtmlService);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->options['label'] ?: $this->getName();
    }

    public function getIconClass(): string
    {
        return $this->options['iconClass'] ?? 'opendxp_icon_workflow_action';
    }

    /**
     * @return string|int|false
     */
    public function getObjectLayout(): bool|int|string
    {
        return $this->options['objectLayout'] ?: false;
    }

    public function getTos(): array
    {
        return $this->options['to'] ?? [];
    }

    public function getGuard(): ?string
    {
        return $this->options['guard'] ?? null;
    }

    public function isGuardValid(WorkflowInterface $workflow, object $subject): bool
    {
        if (empty($this->getGuard())) {
            return true;
        }

        return (bool)$this->expressionService->evaluateExpression($workflow, $subject, $this->getGuard());
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getSaveSubject(): bool
    {
        return $this->options['saveSubject'] ?? true;
    }
}
