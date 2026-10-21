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

namespace OpenDxp\Tests\Feature\GenericExecutionEngine;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRunErrorLog;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use OpenDxp\TestFoundation\Container;

it('lists the errors of one step of a job run', function () {
    $job = createJob([1, 2], [createNoteStep(failFor: [1]), createNoteStep(failFor: [2])]);
    $jobRun = Container::get(JobExecutionAgentInterface::class)->startJobExecution($job, null);
    $this->consumeJobMessages();

    $logs = Container::get(JobRunErrorLogRepositoryInterface::class)->getLogsByJobRunId($jobRun->getId(), 1);

    expect(array_map(static fn (JobRunErrorLog $log): ?int => $log->getElementId(), $logs))->toBe([2]);
});
