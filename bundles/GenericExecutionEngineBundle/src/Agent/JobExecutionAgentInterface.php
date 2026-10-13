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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Agent;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\Job;
use Throwable;

/**
 * Adds the method deleteJobRun() in 2.0.
 *
 * @method void deleteJobRun(int $jobRunId) Deletes an ended job run with its error logs and its artifacts
 */
interface JobExecutionAgentInterface
{
    /**
     * Start new Job Run based on a job definition
     */
    public function startJobExecution(
        Job $job,
        ?int $ownerId,
        string $executionContext = 'default'
    ): JobRun;

    /**
     * Continue execution when a message is finished.
     */
    public function continueJobMessageExecution(
        GenericExecutionEngineMessageInterface $message,
        ?Throwable $throwable = null
    ): void;

    /**
     * checks if interaction with job run is allowed by given user
     */
    public function isInteractionAllowed(int $jobRunId, int $ownerId): bool;

    /**
     * Cancel given job run
     */
    public function cancelJobRun(int $jobRunId): void;

    /**
     * Start new job based on given job run
     */
    public function rerunJobRun(int $jobRunId, ?int $ownerId): void;

    /**
     * Checks if job run is running
     */
    public function isRunning(int $jobRunId): bool;
}
