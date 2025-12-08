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

namespace OpenDxp\Workflow\Notes;

use OpenDxp\Model\Element\ElementInterface;

abstract class AbstractCustomHtmlService implements CustomHtmlServiceInterface
{
    protected string $transitionName = '';

    protected string $actionName = '';

    protected bool $isGlobalAction = false;

    public function __construct(string $actionOrTransitionName, bool $isGlobalAction, protected string $position = '')
    {
        $this->actionName = $isGlobalAction ? $actionOrTransitionName : '';
        $this->transitionName = $isGlobalAction ? '' : $actionOrTransitionName;
        $this->isGlobalAction = $isGlobalAction;
    }

    public function renderHtmlForRequestedPosition(ElementInterface $element, string $requestedPosition): string
    {
        if ($this->getPosition() === $requestedPosition) {
            return $this->renderHtml($element);
        }

        return '';
    }

    final public function getTransitionName(): string
    {
        return $this->transitionName;
    }

    final public function getActionName(): string
    {
        return $this->actionName;
    }

    final public function isGlobalAction(): bool
    {
        return $this->isGlobalAction;
    }

    final public function getPosition(): string
    {
        return $this->position;
    }
}
