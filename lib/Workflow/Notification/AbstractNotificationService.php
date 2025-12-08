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

namespace OpenDxp\Workflow\Notification;

use OpenDxp\Db;
use OpenDxp\Model\Element\Note;
use OpenDxp\Model\User;

class AbstractNotificationService
{
    protected function getNoteInfo(int $id): string
    {
        $noteList = new Note\Listing();
        $noteList->addConditionParam('(cid = ?)', [$id]);
        $noteList->setOrderKey('date');
        $noteList->setOrder('desc');
        $noteList->setLimit(1);

        $notes = $noteList->load();

        if (count($notes) === 1) {
            // found matching note
            return $notes[0]->getDescription();
        }

        return '';
    }

    /**
     * Returns a list of distinct users given an user- and role array containing their respective names
     *
     *
     * @return User[][]
     */
    protected function getNotificationUsersByName(array $users, array $roles, bool $includeAllUsers = false): array
    {
        $notifyUsers = [];

        if ($roles) {
            //get roles
            $roleList = new User\Role\Listing();
            $roleList->setCondition('name IN ('.implode(',', array_map([Db::get(), 'quote'], $roles)).')');

            foreach ($roleList->load() as $role) {
                $userList = new User\Listing();
                $userList->setCondition('FIND_IN_SET(?, roles) > 0 AND active = 1', [$role->getId()]);

                if (!$includeAllUsers) {
                    $userList->addConditionParam('(email IS NOT NULL AND email != "")');
                }

                foreach ($userList->load() as $user) {
                    $notifyUsers[$user->getLanguage()][$user->getId()] = $user;
                }
            }
        }

        if ($users) {
            //get users
            $userList = new User\Listing();
            $userList->setCondition('name IN ('.implode(',', array_map([Db::get(), 'quote'], $users)).') and active = 1');

            if (!$includeAllUsers) {
                $userList->addConditionParam('(email IS NOT NULL AND email != "")');
            }

            foreach ($userList->load() as $user) {
                $notifyUsers[$user->getLanguage()][$user->getId()] = $user;
            }
        }

        foreach ($notifyUsers as $language => $usersPerLanguage) {
            $notifyUsers[$language] = array_values($usersPerLanguage);
        }

        return $notifyUsers;
    }
}
