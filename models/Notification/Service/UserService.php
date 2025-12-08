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

namespace OpenDxp\Model\Notification\Service;

use OpenDxp\Model\User;

/**
 * @internal
 */
class UserService
{
    public function findAll(User $loggedIn): array
    {
        // condition for users with groups having notifications permission
        $condition = [];
        $rolesList = new \OpenDxp\Model\User\Role\Listing();
        $rolesList->addConditionParam("CONCAT(',', permissions, ',') LIKE ?", '%,notifications,%');
        $rolesList->load();
        $roles = $rolesList->getRoles();

        foreach ($roles as $role) {
            $condition[] = "CONCAT(',', roles, ',') LIKE '%," . $role->getId() . ",%'";
        }

        // get available users having notifications permission or having a group with notifications permission
        $userListing = new User\Listing();
        $userListing->setOrderKey('name');
        $userListing->setOrder('ASC');

        $condition[] = 'admin = 1';
        $userListing->addConditionParam("((CONCAT(',', permissions, ',') LIKE ? ) OR " . implode(' OR ', $condition) . ')', '%,notifications,%');
        $userListing->addConditionParam('id != ?', $loggedIn->getId());
        $userListing->addConditionParam('active = ?', '1');
        $userListing->load();
        $users = $userListing->getUsers();

        return [...$users, ...$roles];
    }

    public function filterUsersWithPermission(array $users): array
    {
        $usersList = [];

        /** @var User $user */
        foreach ($users as $user) {
            if ($user->isAllowed('notifications')) {
                $usersList[] = $user;
            }
        }

        return $usersList;
    }
}
