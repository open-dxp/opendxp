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

namespace OpenDxp\Model\Element;

use Exception;
use OpenDxp\Db;
use OpenDxp\Db\Helper;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\User;

/**
 * @internal
 */
class PermissionChecker
{
    /**
     * @param User[] $users
     */
    public static function check(ElementInterface $element, array $users): array
    {
        $protectedColumns = ['cid', 'cpath', 'userId', 'lEdit', 'lView', 'layouts'];

        if ($element instanceof DataObject\AbstractObject) {
            $type = 'object';
        } elseif ($element instanceof Asset) {
            $type = 'asset';
        } elseif ($element instanceof Document) {
            $type = 'document';
        } else {
            throw new Exception('type not supported');
        }
        $db = Db::get();
        $tableName = 'users_workspaces_'.$type;
        $tableDesc = $db->fetchAllAssociative('describe '.$tableName);

        $result = [
            'columns' => [],
        ];

        foreach ($tableDesc as $column) {
            $columnName = $column['Field'];
            if (in_array($columnName, $protectedColumns)) {
                continue;
            }

            $result['columns'][] = $columnName;
        }

        $permissions = [];
        $details = [];

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $userPermission = [];
            $userPermission['userId'] = $user->getId();
            $userPermission['userName'] = $user->getName();

            foreach ($result['columns'] as $columnName) {
                $parentIds = self::collectParentIds($element);

                $userIds = $user->getRoles();

                $userIds[] = $user->getId();

                if ($user->isAdmin()) {
                    $userPermission[$columnName] = true;

                    continue;
                }

                $userPermission[$columnName] = false;

                try {
                    $permissionsParent = $db->fetchAssociative(
                        'SELECT * FROM users_workspaces_'.$type.' , users u WHERE userId = u.id AND cid IN ('.implode(
                            ',',
                            $parentIds
                        ).') AND userId IN ('.implode(
                            ',',
                            $userIds
                        ).') ORDER BY LENGTH(cpath) DESC, FIELD(userId,'.$user->getId().') DESC, `' . $columnName . '` DESC  LIMIT 1'
                    );

                    if ($permissionsParent) {
                        $userPermission[$columnName] = (bool) $permissionsParent[$columnName];

                        $details[] = self::createDetail($user, $columnName, $userPermission[$columnName], $permissionsParent['type'], $permissionsParent['name'], $permissionsParent['cpath']);

                        continue;
                    }

                    // exception for list permission
                    if (false === $permissionsParent && $columnName === 'list') {
                        // check for children with permissions
                        $path = $element->getRealFullPath().'/';
                        if ($element->getId() == 1) {
                            $path = '/';
                        }

                        $permissionsChildren = $db->fetchAssociative(
                            'SELECT list FROM users_workspaces_'.$type.', users u WHERE userId = u.id AND cpath LIKE ? AND userId IN ('.implode(
                                ',',
                                $userIds
                            ).') AND list = 1 LIMIT 1',
                            [Helper::escapeLike($path) .'%']
                        );
                        if ($permissionsChildren) {
                            $result[$columnName] = (bool) $permissionsChildren[$columnName];
                            $details[] = self::createDetail($user, $columnName, $result[$columnName], $permissionsChildren['type'], $permissionsChildren['name'], $permissionsChildren['cpath']);

                            continue;
                        }
                    }
                } catch (Exception) {
                    Logger::warn('Unable to get permission '.$type.' for object '.$element->getId());
                }
            }
            self::getUserPermissions($user, $details);
            self::getLanguagePermissions($user, $element, $details);
            $permissions[] = $userPermission;
        }

        $result['permissions'] = $permissions;

        $result['details'] = $details;

        return $result;
    }

    protected static function collectParentIds(ElementInterface $element): array
    {
        // collect properties via parent - ids
        $parentIds = [1];

        $obj = $element->getParent();
        if ($obj) {
            while ($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        $parentIds[] = $element->getId();

        return $parentIds;
    }

    protected static function createDetail(User $user, ?string $a = null, ?bool $b = null, ?string $c = null, ?string $d = null, ?string $e = null, ?string $f = null): array
    {
        return [
            'userId' => $user->getId(),
            'a' => $a,
            'b' => $b,
            'c' => $c,
            'd' => $d,
            'e' => $e,
            'f' => $f,
        ];
    }

    protected static function getUserPermissions(User $user, array &$details): void
    {
        if ($user->isAdmin()) {
            $details[] = self::createDetail($user, 'ADMIN', true);

            return;
        }
        $details[] = self::createDetail($user, '<b>User Permissions</b>');

        $db = Db::get();
        $permissions = $db->fetchFirstColumn('select `key` from users_permission_definitions');
        foreach ($permissions as $permissionKey) {
            $entry = null;

            if (!$user->getPermission($permissionKey)) {
                // check roles
                foreach ($user->getRoles() as $roleId) {
                    $role = User\Role::getById($roleId);
                    if ($role->getPermission($permissionKey)) {
                        $entry = self::createDetail($user, $permissionKey, true, $role->getType(), $role->getName());

                        break;
                    }
                }
            } else {
                $entry = self::createDetail($user, $permissionKey, true, $user->getType(), $user->getName());
            }

            if (!$entry) {
                $entry = self::createDetail($user, $permissionKey, false);
            }
            $details[] = $entry;
        }
    }

    protected static function getLanguagePermissions(User $user, ElementInterface $element, array &$details): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($element instanceof DataObject\AbstractObject) {
            $details[] = self::createDetail($user, '<b>Language Permissions</b>');

            $permissions = ['lView' => 'view', 'lEdit' => 'edit'];
            foreach ($permissions as $permissionKey => $permissionName) {
                $languagePermissions = DataObject\Service::getLanguagePermissions($element, $user, $permissionKey);
                if (!$languagePermissions) {
                    $languagePermissions = 'all';
                } else {
                    $languagePermissions = array_keys($languagePermissions);
                    $languagePermissions = implode(', ', $languagePermissions);
                }

                $details[] = self::createDetail($user, $permissionName, null, null, null, $languagePermissions);
            }
        }
    }
}
