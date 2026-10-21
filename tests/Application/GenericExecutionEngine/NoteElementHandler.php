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

use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Handler\AbstractAutomationActionHandler;
use RuntimeException;

/**
 * Notes the step and the element of every message in the context of the job run.
 *
 * The step configuration `failFor` lists the IDs of failing elements.
 */
final class NoteElementHandler extends AbstractAutomationActionHandler
{
    public function __invoke(NoteElementMessage $message): void
    {
        $id = $message->getElement()?->getId();
        if (in_array($id, $this->getCurrentJobStepConfig($message)['failFor'], true)) {
            throw new RuntimeException(sprintf('The element %d fails.', $id));
        }

        $jobRun = $this->getJobRun($message);
        $notes = $jobRun->getContext()['notes'] ?? [];
        $notes[] = sprintf('%d:%s', $message->getCurrentJobStep(), $id ?? 'all');

        $this->updateJobRunContext($jobRun, 'notes', $notes);
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setDefault('failFor', []);
    }
}
