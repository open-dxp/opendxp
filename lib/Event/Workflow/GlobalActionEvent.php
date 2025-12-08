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

namespace OpenDxp\Event\Workflow;

use OpenDxp\Event\Traits\ArgumentsAwareTrait;
use OpenDxp\Workflow\GlobalAction;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\Event;

class GlobalActionEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(
        protected WorkflowInterface $workflow,
        protected mixed $subject,
        protected GlobalAction $globalAction,
        array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function getWorkflow(): WorkflowInterface
    {
        return $this->workflow;
    }

    public function getSubject(): mixed
    {
        return $this->subject;
    }

    public function getGlobalAction(): GlobalAction
    {
        return $this->globalAction;
    }
}
