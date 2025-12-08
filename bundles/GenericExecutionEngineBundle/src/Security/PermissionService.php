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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Security;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\PermissionConstants;
use OpenDxp\Model\UserInterface;
use OpenDxp\Tool\Authentication;

/**
 * @internal
 */
final readonly class PermissionService implements PermissionServiceInterface
{
    private ?UserInterface $user;

    public function __construct(
    ) {
        $this->user = Authentication::authenticateSession();
    }

    public function allowedToSeeJobRuns(): void
    {
        if (!$this->isAllowedToSeeJobRuns()) {
            throw new PermissionException('You are not allowed to see job run.');
        }
    }

    public function allowedToSeeAllJobRuns(): void
    {
        if (!$this->isAllowedToSeeAllJobRuns()) {
            throw new PermissionException(
                'You are not allowed to see all job runs. You can just see your own job runs.'
            );
        }
    }

    public function isAllowedToSeeJobRuns(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->isAllowed(PermissionConstants::GEE_JOB_RUN);
    }

    public function isAllowedToSeeAllJobRuns(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->isAllowed(PermissionConstants::GEE_SEE_ALL_JOB_RUNS);
    }
}
