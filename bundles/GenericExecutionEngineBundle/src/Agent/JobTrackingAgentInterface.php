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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Agent;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;
use Throwable;

interface JobTrackingAgentInterface
{
    /**
     * @param callable(JobProgress): void $callback
     *
     * @throws ExecutionContextNotDefinedException
     * @throws Throwable the exception of the callback
     */
    public function trackJob(
        string $jobName,
        callable $callback,
        ?int $ownerId = null,
        string $executionContext = 'default',
    ): void;
}
