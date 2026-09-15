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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Security;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use OpenDxp\Model\UserInterface;
use OpenDxp\Security\PermissionAttribute;
use OpenDxp\Tool\Authentication;

/**
 * @internal
 */
final class PermissionService implements PermissionServiceInterface
{
    private ?UserInterface $user = null;

    private bool $userResolved = false;

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
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        return $user->isAllowed(PermissionAttribute::for(GenericExecutionEnginePermission::JobRun->value));
    }

    public function isAllowedToSeeAllJobRuns(): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        return $user->isAllowed(PermissionAttribute::for(GenericExecutionEnginePermission::SeeAllJobRuns->value));
    }

    private function getUser(): ?UserInterface
    {
        if (!$this->userResolved) {
            $this->user = Authentication::authenticateSession();
            $this->userResolved = true;
        }

        return $this->user;
    }
}
