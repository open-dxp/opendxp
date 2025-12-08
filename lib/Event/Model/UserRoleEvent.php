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

namespace OpenDxp\Event\Model;

use OpenDxp\Model\User\AbstractUser;
use Symfony\Contracts\EventDispatcher\Event;

class UserRoleEvent extends Event
{
    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(protected AbstractUser $userRole)
    {
    }

    public function getUserRole(): AbstractUser
    {
        return $this->userRole;
    }

    public function setUserRole(AbstractUser $userRole): void
    {
        $this->userRole = $userRole;
    }
}
