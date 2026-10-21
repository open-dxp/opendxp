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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Model;

/**
 * @internal
 */
final readonly class JobRunFilter
{
    public function __construct(
        public ?int $ownerId = null,
        public bool $withoutOwner = false,
        public ?string $jobName = null,
        public ?JobRunStates $state = null,
        public ?int $startedFrom = null,
        public ?int $startedUntil = null,
    ) {
    }
}
