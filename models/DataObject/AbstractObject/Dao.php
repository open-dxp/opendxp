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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\DataObject\AbstractObject;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use OpenDxp\Db\Helper;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\AbstractObject $model
 */
class Dao extends Model\Element\Dao
{
    /**
     * Get the data for the object from database for the given id
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $this->initByRow($this->getDataRowById($id));
    }

    /**
     * Fetch the full object row (incl. tree lock state) as used by getById()
     *
     * @internal
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getDataRowById(int $id): array
    {
        $data = $this->db->fetchAssociative(
            'SELECT objects.*, tree_locks.locked as locked FROM objects
                LEFT JOIN tree_locks ON objects.id = tree_locks.id AND tree_locks.type = "object"
                WHERE objects.id = ?',
            [$id]
        );

        if (!$data) {
            throw new Model\Exception\NotFoundException('Object with the ID ' . $id . " doesn't exists");
        }

        return $data;
    }

    /**
     * Initialize the model from an already fetched object row, avoiding a
     * second query when the row is available from getDataRowById()
     *
     * @internal
     */
    public function initByRow(array $data): void
    {
        $data['published'] = (bool)$data['published'];
        $this->assignVariablesToModel($data);
    }

    /**
     * Get the data for the object from database for the given path
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByPath(string $path): void
    {
        $params = $this->extractKeyAndPath($path);
        $data = $this->db->fetchAssociative('SELECT id FROM objects WHERE `path` = BINARY :path AND `key` = BINARY :key', $params);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException("object doesn't exist");
        }
    }

    /**
     * Create a new record for the object in database
     */
    public function create(): void
    {
        $this->db->insert('objects', Helper::quoteDataIdentifiers($this->db, [
            'key' => $this->model->getKey(),
            'path' => $this->model->getRealPath(),
        ]));
        $this->model->setId((int) $this->db->lastInsertId());

        if (!$this->model->getKey() && !is_numeric($this->model->getKey())) {
            $this->model->setKey($this->db->lastInsertId());
        }
    }

    /**
     * @throws \Exception
     */
    public function update(?bool $isUpdate = null): void
    {
        $object = $this->model->getObjectVars();

        $data = [];
        $validTableColumns = $this->getValidTableColumns('objects');

        foreach ($object as $key => $value) {
            if (in_array($key, $validTableColumns)) {
                if (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        // check the type before updating, changing the type or class of an object is not possible
        $checkColumns = ['type', 'classId', 'className'];
        $existingData = $this->db->fetchAssociative('SELECT type, classId, className FROM objects WHERE id = ?', [$this->model->getId()]);
        foreach ($checkColumns as $column) {
            if ($column === 'type' && in_array($data[$column], [DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT]) && (isset($existingData[$column]) && in_array($existingData[$column], [DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT]))) {
                // type conversion variant <=> object should be possible
                continue;
            }

            if (!empty($existingData[$column]) && $data[$column] != $existingData[$column]) {
                throw new \Exception('Unable to save object: type, classId or className mismatch');
            }
        }

        Helper::upsert($this->db, 'objects', $data, $this->getPrimaryKey('objects'));

        // tree_locks
        $this->db->delete('tree_locks', ['id' => $this->model->getId(), 'type' => 'object']);
        if ($this->model->getLocked()) {
            $this->db->insert('tree_locks', [
                'id' => $this->model->getId(),
                'type' => 'object',
                'locked' => $this->model->getLocked(),
            ]);
        }
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('objects', ['id' => $this->model->getId()]);
    }

    public function updateWorkspaces(): void
    {
        $this->db->update('users_workspaces_object', [
            'cpath' => $this->model->getRealFullPath(),
        ], [
            'cid' => $this->model->getId(),
        ]);
    }

    /**
     * Updates the paths for children, children's properties and children's permissions in the database
     *
     * @internal
     */
    public function updateChildPaths(string $oldPath): ?array
    {
        if ($this->hasChildren(DataObject::$types, true)) {
            //get objects to empty their cache
            $objects = $this->db->fetchFirstColumn('SELECT id FROM objects WHERE `path` like ?', [Helper::escapeLike($oldPath) . '%']);

            $userId = '0';
            if ($user = \OpenDxp\Tool\Admin::getCurrentUser()) {
                $userId = $user->getId();
            }

            $newPath = $this->model->getRealFullPath();

            //update object child paths
            // we don't update the modification date here, as this can have side-effects when there's an unpublished version for an element
            $this->db->executeStatement(
                'UPDATE objects SET `path` = REPLACE(`path`, ?, ?), userModification = ? WHERE `path` LIKE ?',
                [$oldPath . '/', $newPath . '/', $userId, Helper::escapeLike($oldPath) . '/%']
            );

            //update object child permission paths
            $this->db->executeStatement(
                'UPDATE users_workspaces_object SET cpath = REPLACE(cpath, ?, ?) WHERE cpath LIKE ?',
                [$oldPath . '/', $newPath . '/', Helper::escapeLike($oldPath) . '/%']
            );

            //update object child properties paths
            $this->db->executeStatement(
                'UPDATE properties SET cpath = REPLACE(cpath, ?, ?) WHERE cpath LIKE ?',
                [$oldPath . '/', $newPath . '/', Helper::escapeLike($oldPath) . '/%']
            );

            return $objects;
        }

        return null;
    }

    /**
     * deletes all properties for the object from database
     */
    public function deleteAllProperties(): void
    {
        $this->db->delete('properties', ['cid' => $this->model->getId(), 'ctype' => 'object']);
    }

    /**
     * @return string|null retrieves the current full object path from DB
     */
    public function getCurrentFullPath(): ?string
    {
        if ($path = $this->db->fetchOne('SELECT CONCAT(`path`,`key`) as `path` FROM objects WHERE id = ?', [$this->model->getId()])) {
            return $path;
        }

        return null;
    }

    public function getVersionCountForUpdate(): int
    {
        if (!$this->model->getId()) {
            return 0;
        }

        $versionCount = (int) $this->db->fetchOne('SELECT versionCount FROM objects WHERE id = ? FOR UPDATE', [$this->model->getId()]);

        if ($this->model instanceof DataObject\Concrete) {
            $versionCount2 = (int) $this->db->fetchOne('SELECT MAX(versionCount) FROM versions WHERE cid = ? AND ctype = "object"', [$this->model->getId()]);
            $versionCount = max($versionCount, $versionCount2);
        }

        return (int) $versionCount;
    }

    /**
     * Get the properties for the object from database and assign it
     *
     * @throws \Exception
     */
    public function getProperties(bool $onlyInherited = false): array
    {
        $properties = [];

        // collect properties via parent - ids
        $parentIds = $this->getParentIds();
        $propertiesRaw = $this->db->fetchAllAssociative(
            'SELECT name, type, data, cid, inheritable, cpath FROM properties WHERE ((cid IN (?) AND inheritable = 1) OR cid = ?) AND ctype="object"',
            [$parentIds, $this->model->getId()],
            [ArrayParameterType::INTEGER, ParameterType::INTEGER]
        );

        // because this should be faster than mysql
        usort($propertiesRaw, fn ($left, $right) => strcmp((string)$left['cpath'], (string)$right['cpath']));

        foreach ($propertiesRaw as $propertyRaw) {
            try {
                $id = $this->model->getId();
                $property = new Model\Property();
                $property->setType($propertyRaw['type']);
                if ($id !== null) {
                    $property->setCid($id);
                }
                $property->setName($propertyRaw['name']);
                $property->setCtype('object');
                $property->setDataFromResource($propertyRaw['data']);
                $property->setInherited(true);
                if ($propertyRaw['cid'] == $this->model->getId()) {
                    $property->setInherited(false);
                }
                $property->setInheritable(false);
                if ($propertyRaw['inheritable']) {
                    $property->setInheritable(true);
                }

                if ($onlyInherited && !$property->getInherited()) {
                    continue;
                }

                $properties[$propertyRaw['name']] = $property;
            } catch (\Exception) {
                Logger::error(
                    "can't add property " . $propertyRaw['name'] . ' to object ' . $this->model->getRealFullPath()
                );
            }
        }

        return $properties;
    }

    /**
     * Quick test if there are children
     *
     * @throws Exception
     */
    public function hasChildren(
        array $objectTypes = [
            DataObject::OBJECT_TYPE_OBJECT,
            DataObject::OBJECT_TYPE_VARIANT,
            DataObject::OBJECT_TYPE_FOLDER,
        ],
        ?bool $includingUnpublished = null,
        ?User $user = null
    ): bool {
        if (!$this->model->getId()) {
            return false;
        }

        $sql = 'SELECT 1 FROM objects o WHERE parentId = ?';
        $params = [$this->model->getId()];
        $types = [ParameterType::INTEGER];

        if ($user && !$user->isAdmin()) {
            $roleIds = array_map('intval', $user->getRoles());
            $currentUserId = $user->getId();
            $permissionIds = [...$roleIds, $currentUserId];

            //gets the permission of the ancestors, since it would be the same for each row with same parentId, it is done once outside the query to avoid extra subquery.
            $inheritedPermission = $this->isInheritingPermission('list', $permissionIds);

            // $anyAllowedRowOrChildren checks for nested elements that are `list`=1.
            // This is to allow the folders in between from current parent to any nested elements and due the "additive" permission on the element itself, we can simply ignore list=0 children
            // unless for the same rule found is list=0 on user specific level, in that case it nullifies that entry.
            $anyAllowedRowOrChildren = 'EXISTS(
                SELECT list FROM users_workspaces_object uwo
                WHERE userId IN (?)
                AND list=1
                AND LOCATE(CONCAT(o.path,o.key),cpath)=1
                AND NOT EXISTS(
                    SELECT list FROM users_workspaces_object
                    WHERE userId=? AND list=0 AND cpath = uwo.cpath
                )
            )';

            // $isDisallowedCurrentRow checks if the current row is blocked, if found a match it "removes/ignores" the entry from object table,
            // doesn't need to check if is list=1 on user level, since it is done in $anyAllowedRowOrChildren (NB: equal or longer cpath) so we are safe to deduce that there are no valid list=1 rules
            $isDisallowedCurrentRow = 'EXISTS(
                SELECT list FROM users_workspaces_object uworow
                WHERE userId IN (?)
                AND cid = id
                AND list=0
            )';

            // If no children with list=1 (with no user-level list=0) is found, we consider the inherited permission rule
            // if $inheritedPermission=0 then everything is disallowed (or doesn't specify any rule) for that row, we can skip $isDisallowedCurrentRow
            // if $inheritedPermission=1, then we are allowed unless the current row is specifically disabled,
            // already knowing from $anyAllowedRowOrChildren that there are no list=1(without user permission list=0),so this "blocker" is the highest cpath available for this row if found
            $sql .= sprintf(' AND IF(%s,1,IF(%d,%s = 0,0)) = 1', $anyAllowedRowOrChildren, $inheritedPermission, $isDisallowedCurrentRow);

            $params[] = $permissionIds;       // for $anyAllowedRowOrChildren IN (?)
            $types[] = ArrayParameterType::INTEGER;
            $params[] = $currentUserId;       // for $anyAllowedRowOrChildren userId=?
            $types[] = ParameterType::INTEGER;
            $params[] = $permissionIds;       // for $isDisallowedCurrentRow IN (?)
            $types[] = ArrayParameterType::INTEGER;
        }

        $includingUnpublished ??= !DataObject::doHideUnpublished();
        if (!$includingUnpublished) {
            $sql .= ' AND published = 1';
        }

        if ($objectTypes) {
            $sql .= ' AND `type` IN (?)';
            $params[] = $objectTypes;
            $types[] = ArrayParameterType::STRING;
        }

        $sql .= ' LIMIT 1';

        $c = $this->db->fetchOne($sql, $params, $types);

        return (bool)$c;
    }

    /**
     * Quick test if there are siblings
     *
     * @throws Exception
     */
    public function hasSiblings(
        array $objectTypes = [
            DataObject::OBJECT_TYPE_OBJECT,
            DataObject::OBJECT_TYPE_VARIANT,
            DataObject::OBJECT_TYPE_FOLDER,
        ],
        ?bool $includingUnpublished = null
    ): bool {
        if (!$this->model->getParentId()) {
            return false;
        }

        $sql = 'SELECT 1 FROM objects WHERE parentId = ?';
        $params = [$this->model->getParentId()];
        $types = [ParameterType::INTEGER];

        if ($this->model->getId()) {
            $sql .= ' AND id != ?';
            $params[] = $this->model->getId();
            $types[] = ParameterType::INTEGER;
        }

        $includingUnpublished ??= !DataObject::doHideUnpublished();
        if (!$includingUnpublished) {
            $sql .= ' AND published = 1';
        }

        if ($objectTypes) {
            $sql .= ' AND `type` IN (?)';
            $params[] = $objectTypes;
            $types[] = ArrayParameterType::STRING;
        }

        $sql .= ' LIMIT 1';

        $c = $this->db->fetchOne($sql, $params, $types);

        return (bool)$c;
    }

    /**
     * returns the amount of directly children (not recursivly)
     */
    public function getChildAmount(
        ?array $objectTypes = [
            DataObject::OBJECT_TYPE_OBJECT,
            DataObject::OBJECT_TYPE_VARIANT,
            DataObject::OBJECT_TYPE_FOLDER,
        ],
        ?User $user = null
    ): int {
        if (!$this->model->getId()) {
            return 0;
        }

        $params = [$this->model->getId()];
        $types = [ParameterType::INTEGER];
        $query = 'SELECT COUNT(*) AS count FROM objects o WHERE parentId = ?';

        if ($objectTypes) {
            $query .= ' AND `type` IN (?)';
            $params[] = $objectTypes;
            $types[] = ArrayParameterType::STRING;
        }

        if ($user && !$user->isAdmin()) {
            $roleIds = array_map('intval', $user->getRoles());
            $currentUserId = $user->getId();
            $permissionIds = [...$roleIds, $currentUserId];

            $inheritedPermission = $this->isInheritingPermission('list', $permissionIds);

            $anyAllowedRowOrChildren = 'EXISTS(
                SELECT list FROM users_workspaces_object uwo
                WHERE userId IN (?)
                AND list=1
                AND LOCATE(CONCAT(o.path,o.key),cpath)=1
                AND NOT EXISTS(
                    SELECT list FROM users_workspaces_object
                    WHERE userId=? AND list=0 AND cpath = uwo.cpath
                )
            )';
            $isDisallowedCurrentRow = 'EXISTS(
                SELECT list FROM users_workspaces_object uworow
                WHERE userId IN (?)
                AND cid = id
                AND list=0
            )';

            $query .= sprintf(' AND IF(%s,1,IF(%d,%s = 0,0)) = 1', $anyAllowedRowOrChildren, $inheritedPermission, $isDisallowedCurrentRow);

            $params[] = $permissionIds;
            $types[] = ArrayParameterType::INTEGER;
            $params[] = $currentUserId;
            $types[] = ParameterType::INTEGER;
            $params[] = $permissionIds;
            $types[] = ArrayParameterType::INTEGER;
        }

        return (int) $this->db->fetchOne($query, $params, $types);
    }

    /**
     * @throws Model\Exception\NotFoundException
     */
    public function getTypeById(int $id): array
    {
        $t = $this->db->fetchAssociative('SELECT `type`,`className`,`classId` FROM objects WHERE `id` = ?', [$id]);

        if (!$t) {
            throw new Model\Exception\NotFoundException('object with ID ' . $id . ' not found');
        }

        return $t;
    }

    public function isLocked(): bool
    {
        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne(
            'SELECT tree_locks.id FROM tree_locks INNER JOIN objects ON tree_locks.id = objects.id WHERE objects.path LIKE ? AND tree_locks.type = "object" AND tree_locks.locked IS NOT NULL AND tree_locks.locked != "" LIMIT 1',
            [Helper::escapeLike($this->model->getRealFullPath()) . '/%']
        );

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne(
            'SELECT id FROM tree_locks WHERE id IN (?) AND `type` = "object" AND locked = "propagate" LIMIT 1',
            [$parentIds],
            [ArrayParameterType::INTEGER]
        );

        return $inhertitedLocks > 0;
    }

    public function unlockPropagate(): array
    {
        $lockIds = $this->db->fetchFirstColumn(
            'SELECT id FROM objects WHERE `path` LIKE ? OR id = ?',
            [Helper::escapeLike($this->model->getRealFullPath()) . '/%', $this->model->getId()]
        );

        $this->db->executeStatement(
            'DELETE FROM tree_locks WHERE `type` = "object" AND id IN (?)',
            [$lockIds],
            [ArrayParameterType::INTEGER]
        );

        return $lockIds;
    }

    /**
     * @return DataObject\ClassDefinition[]
     */
    public function getClasses(): array
    {
        $path = $this->model->getRealFullPath();
        if (!$this->model->getId() || $this->model->getId() == 1) {
            $path = '';
        }

        $classIds = $this->db->fetchFirstColumn(
            'SELECT DISTINCT classId FROM objects WHERE `path` like ? AND `type` = "object"',
            [Helper::escapeLike($path) . '/%']
        );

        $classes = [];
        foreach ($classIds as $classId) {
            if ($class = DataObject\ClassDefinition::getById($classId)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * @return int[]
     */
    protected function collectParentIds(): array
    {
        $parentIds = $this->getParentIds();
        if ($id = $this->model->getId()) {
            $parentIds[] = $id;
        }

        return $parentIds;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function isInheritingPermission(string $type, array $userIds): int
    {
        return $this->InheritingPermission($type, $userIds, 'object');
    }

    public function isAllowed(string $type, User $user): bool
    {
        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne(
                'SELECT ' . $this->db->quoteIdentifier($type) . ' FROM users_workspaces_object WHERE cid IN (?) AND userId IN (?) ORDER BY LENGTH(cpath) DESC, FIELD(userId, ?) DESC, ' . $this->db->quoteIdentifier($type) . ' DESC LIMIT 1',
                [$parentIds, $userIds, $user->getId()],
                [ArrayParameterType::INTEGER, ArrayParameterType::INTEGER, ParameterType::INTEGER]
            );

            if ($permissionsParent) {
                return true;
            }

            // exception for list permission
            if (empty($permissionsParent) && $type === 'list') {
                // check for children with permissions
                $path = $this->model->getRealFullPath() . '/';
                if ($this->model->getId() == 1) {
                    $path = '/';
                }

                $permissionsChildren = $this->db->fetchOne(
                    'SELECT list FROM users_workspaces_object WHERE cpath LIKE ? AND userId IN (?) AND list = 1 LIMIT 1',
                    [Helper::escapeLike($path) . '%', $userIds],
                    [ParameterType::STRING, ArrayParameterType::INTEGER]
                );
                if ($permissionsChildren) {
                    return true;
                }
            }
        } catch (\Exception) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return false;
    }

    /**
     * @param string[] $columns
     *
     * @return array<string, int>
     */
    public function areAllowed(array $columns, User $user): array
    {
        return $this->permissionByTypes($columns, $user, 'object');
    }

    public function getPermissions(?string $type, User $user, bool $quote = true): ?array
    {
        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $queryType = $type && $quote ? '`' . $type . '`' : '*';

            $commaSeparated = in_array($type, ['lView', 'lEdit', 'layouts']);

            if ($commaSeparated) {
                $allPermissions = $this->db->fetchAllAssociative(
                    'SELECT ' . $queryType . ',cid,cpath FROM users_workspaces_object WHERE cid IN (?) AND userId IN (?) ORDER BY LENGTH(cpath) DESC, FIELD(userId, ?) DESC, `' . $type . '` DESC',
                    [$parentIds, $userIds, $user->getId()],
                    [ArrayParameterType::INTEGER, ArrayParameterType::INTEGER, ParameterType::INTEGER]
                );
                if (!$allPermissions) {
                    return null;
                }

                if (count($allPermissions) === 1) {
                    return $allPermissions[0];
                }

                $firstPermission = $allPermissions[0];
                $firstPermissionCid = $firstPermission['cid'];
                $mergedPermissions = [];

                foreach ($allPermissions as $permission) {
                    $cid = $permission['cid'];
                    if ($cid != $firstPermissionCid) {
                        break;
                    }

                    $permissionValues = $permission[$type];
                    if (!$permissionValues) {
                        $firstPermission[$type] = null;

                        return $firstPermission;
                    }

                    $permissionValues = explode(',', $permissionValues);
                    foreach ($permissionValues as $permissionValue) {
                        $mergedPermissions[$permissionValue] = $permissionValue;
                    }
                }

                $firstPermission[$type] = implode(',', $mergedPermissions);

                return $firstPermission;
            }

            $orderByType = $type ? ', `' . $type . '` DESC' : '';
            $permissions = $this->db->fetchAssociative(
                'SELECT ' . $queryType . ' FROM users_workspaces_object WHERE cid IN (?) AND userId IN (?) ORDER BY LENGTH(cpath) DESC, FIELD(userId, ?) DESC' . $orderByType . ' LIMIT 1',
                [$parentIds, $userIds, $user->getId()],
                [ArrayParameterType::INTEGER, ArrayParameterType::INTEGER, ParameterType::INTEGER]
            );

            return $permissions ?: null;
        } catch (\Exception) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return null;
    }

    public function getChildPermissions(?string $type, User $user, bool $quote = true): array
    {
        $userIds = $user->getRoles();
        $userIds[] = $user->getId();
        $permissions = [];

        try {
            $type = $type && $quote ? '`' . $type . '`' : '*';

            $cid = $this->model->getId();
            $permissions = $this->db->fetchAllAssociative(
                'SELECT ' . $type . ' FROM users_workspaces_object WHERE cid != ? AND cpath LIKE ? AND userId IN (?) ORDER BY LENGTH(cpath) DESC',
                [$cid, Helper::escapeLike($this->model->getRealFullPath()) . '%', $userIds],
                [ParameterType::INTEGER, ParameterType::STRING, ArrayParameterType::INTEGER]
            );
        } catch (\Exception) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return $permissions;
    }

    public function saveIndex(int $index): void
    {
        $this->db->update('objects', [
            $this->db->quoteIdentifier('index') => $index,
        ], [
            'id' => $this->model->getId(),
        ]);
    }

    public function __isBasedOnLatestData(): bool
    {
        $data = $this->db->fetchAssociative('SELECT modificationDate, versionCount FROM objects WHERE id = ?', [$this->model->getId()]);

        return $data
            && $data['modificationDate'] == $this->model->__getDataVersionTimestamp()
            && $data['versionCount'] == $this->model->getVersionCount();
    }
}
