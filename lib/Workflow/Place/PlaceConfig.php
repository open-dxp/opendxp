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

namespace OpenDxp\Workflow\Place;

use OpenDxp\Helper\ContrastColor;
use OpenDxp\Workflow\ExpressionService;
use Symfony\Component\Workflow\WorkflowInterface;

class PlaceConfig
{
    public function __construct(private readonly string $place, private array $placeConfigArray, private readonly ExpressionService $expressionService, private readonly string $workflowName)
    {
    }

    public function getLabel(): string
    {
        return $this->placeConfigArray['label'] ?? $this->place;
    }

    public function getTitle(): string
    {
        return $this->placeConfigArray['title'] ?? '';
    }

    public function getColor(): string
    {
        return $this->placeConfigArray['color'] ?? '#bfdadc';
    }

    public function getColorInverted(): bool
    {
        return $this->placeConfigArray['colorInverted'] ?? false;
    }

    public function getBackgroundColor(): string
    {
        return $this->getColorInverted() ? ContrastColor::getContrastColor($this->getColor()) : $this->getColor();
    }

    public function getFontColor(): string
    {
        return $this->getColorInverted() ? $this->getColor() : ContrastColor::getContrastColor($this->getColor());
    }

    public function getBorderColor(): string
    {
        return $this->getColor();
    }

    public function isVisibleInHeader(): bool
    {
        return $this->placeConfigArray['visibleInHeader'];
    }

    public function getObjectLayout(WorkflowInterface $workflow, object $subject): ?string
    {
        return $this->getPermissions($workflow, $subject)['objectLayout'] ?? null;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getPlaceConfigArray(): array
    {
        return $this->placeConfigArray;
    }

    public function getPermissions(WorkflowInterface $workflow, object $subject): array
    {
        foreach ($this->placeConfigArray['permissions'] ?? [] as $permission) {
            $condition = $permission['condition'] ?? false;
            if ($condition && !$this->expressionService->evaluateExpression($workflow, $subject, $condition)) {
                continue;
            }

            return $permission;
        }

        return [];
    }

    public function getUserPermissions(WorkflowInterface $workflow, object $subject): array
    {
        $result = [];

        foreach ($this->getPermissions($workflow, $subject) as $permission => $value) {
            if (in_array($permission, ['save', 'publish', 'unpublish', 'delete', 'view', 'rename', 'settings', 'versions', 'properties'])) {
                $result[$permission] = $value;
            } elseif (in_array($permission, ['lEdit', 'lView'])) {
                $result[$permission] = implode(',', $value);
            } elseif ($permission === 'modify') {
                $result['save'] = $value;
                $result['publish'] = $value;
                $result['unpublish'] = $value;
                $result['delete'] = $value;
                $result['rename'] = $value;
            }
        }

        return $result;
    }
}
