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

namespace OpenDxp\Workflow\SupportStrategy;

use OpenDxp\Workflow\ExpressionService;
use Symfony\Component\Workflow\SupportStrategy\WorkflowSupportStrategyInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @author Andreas Kleemann <akleemann@inviqa.com>
 */
class ExpressionSupportStrategy implements WorkflowSupportStrategyInterface
{
    /**
     * ExpressionSupportStrategy constructor.
     *
     * @param string|string[] $className a FQCN
     */
    public function __construct(private readonly ExpressionService $expressionService, private readonly string|array $className, private readonly string $expression)
    {
    }

    public function supports(WorkflowInterface $workflow, object $subject): bool
    {
        if (!$this->supportsClass($subject)) {
            return false;
        }

        $ret = $this->expressionService->evaluateExpression($workflow, $subject, $this->expression);

        return filter_var($ret, FILTER_VALIDATE_BOOL) && (bool)$ret;
    }

    private function supportsClass(object $subject): bool
    {
        if (is_string($this->className)) {
            return $subject instanceof $this->className;
        }

        foreach ($this->className as $className) {
            if ($subject instanceof $className) {
                return true;
            }
        }

        return false;
    }

    public function getClassName(): array|string
    {
        return $this->className;
    }
}
