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

use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\JobRunMessageInterface;

/**
 * Starts a branch of work. A branch with depth dispatches two branches one level deeper.
 */
final readonly class BranchMessage implements JobRunMessageInterface
{
    public function __construct(
        public ?int $jobRunId,
        public string $path,
        public int $depth,
    ) {
    }

    public function getJobRunId(): ?int
    {
        return $this->jobRunId;
    }
}
