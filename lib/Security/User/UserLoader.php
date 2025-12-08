<?php

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

namespace OpenDxp\Security\User;

use OpenDxp\Http\RequestHelper;
use OpenDxp\Model\User as UserModel;
use OpenDxp\Tool\Authentication;

/**
 * Loads user either from token storage (when inside admin firewall) or directly from session and keeps it in cache. This
 * is mainly needed from event listeners outside the admin firewall to access the user object without needing to open the
 * session multiple times.
 */
class UserLoader
{
    protected ?UserModel $user = null;

    public function __construct(protected TokenStorageUserResolver $userResolver, protected RequestHelper $requestHelper)
    {
    }

    public function getUser(): ?UserModel
    {
        if (!$this->user instanceof \OpenDxp\Model\User) {
            $user = $this->loadUser();

            if ($user) {
                $this->user = $user;
            }
        }

        return $this->user;
    }

    public function setUser(UserModel $user): void
    {
        $this->user = $user;
    }

    protected function loadUser(): ?UserModel
    {
        // authenticated admin user inside admin firewall and set on token storage
        if ($user = $this->userResolver->getUser()) {
            return $user;
        }

        // try to directly authenticate
        if ($this->requestHelper->isFrontendRequestByAdmin()) {
            return Authentication::authenticateSession($this->requestHelper->getCurrentRequest());
        }

        return null;
    }
}
