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

namespace OpenDxp\Model\Document;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Exception;
use OpenDxp\Db\Helper;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\User;
use OpenDxp\Tool\Serialize;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Document $model
 */
class Dao extends Model\Element\Dao
{
    use Model\Element\Traits\ScheduledTasksDaoTrait;

    /**
     * Fetch a row by an id from the database and assign variables to the document model.
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative(
            'SELECT documents.*, tree_locks.locked FROM documents
                LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = "document"
                WHERE documents.id = ?',
            [$id]
        );

        if ($data) {
            $data['published'] = (bool)$data['published'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('document with id ' . $id . ' not found');
        }
    }

    /**
     * Fetch a row by a path from the database and assign variables to the model.
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByPath(string $path): void
    {
        $id = $this->getIdByPath($path);

        if (!$id) {
            // try to find a page with a pretty URL (use the original $path)
            $id = $this->db->fetchOne(
                'SELECT documents_page.id FROM documents_page
                    JOIN documents ON documents.id = documents_page.id
                    WHERE documents_page.prettyUrl = :prettyUrl AND documents.type = :type',
                [
                    'prettyUrl' => $path,
                    'type'      => 'page',
                ]
            );
        }

        if (!$id) {
            throw new Model\Exception\NotFoundException(sprintf('document with path %s does not exist', $path));
        }

        $this->assignVariablesToModel(['id' => $id]);
    }

    /**
     * Looks up the path in the tree only. A pretty URL is a routing alias,
     * not a place in the tree, so it must not answer here.
     */
    public function getIdByPath(string $path): ?int
    {
        $id = $this->db->fetchOne(
            'SELECT id FROM documents WHERE `path` = BINARY :path AND `key` = BINARY :key',
            $this->extractKeyAndPath($path)
        );

        return $id ? (int) $id : null;
    }

    public function create(): void
    {
        $this->db->insert('documents', Helper::quoteDataIdentifiers($this->db, [
            'key' => $this->model->getKey(),
            'type' => $this->model->getType(),
            'path' => $this->model->getRealPath(),
            'parentId' => $this->model->getParentId(),
            'index' => 0,
        ]));

        $this->model->setId((int) $this->db->lastInsertId());

        if (!$this->model->getKey()) {
            $this->model->setKey((string) $this->model->getId());
        }
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        $typeSpecificTable = null;
        $validColumnsTypeSpecific = [];
        $documentsConfig = \OpenDxp\Config::getSystemConfiguration('documents');
        $validTables = [];
        foreach ($documentsConfig['type_definitions']['map'] as $config) {
            if (isset($config['valid_table']) && $config['valid_table']) {
                $validTables[] = $config['valid_table'];
            }
        }

        if (in_array($this->model->getType(), $validTables)) {
            $typeSpecificTable = 'documents_' . $this->model->getType();
            $validColumnsTypeSpecific = $this->getValidTableColumns($typeSpecificTable);
        }

        $document = $this->model->getObjectVars();

        $dataDocument = [];
        $dataTypeSpecific = [];

        foreach ($document as $key => $value) {
            // check if the getter exists
            $getter = 'get' . ucfirst($key);
            if (!method_exists($this->model, $getter)) {
                continue;
            }

            // get the value from the getter
            if (in_array($key, $this->getValidTableColumns('documents')) || in_array($key, $validColumnsTypeSpecific)) {
                $value = $this->model->$getter();
            } else {
                continue;
            }

            if (is_bool($value)) {
                $value = (int)$value;
            }
            if (is_array($value)) {
                $value = Serialize::serialize($value);
            }

            if (in_array($key, $this->getValidTableColumns('documents'))) {
                $dataDocument[$key] = $value;
            }
            if (in_array($key, $validColumnsTypeSpecific)) {
                $dataTypeSpecific[$key] = $value;
            }
        }

        // use the real document path, just for the case that a documents gets saved in the frontend
        // and the page is within a site.
        $dataDocument['path'] = $this->model->getRealPath();

        // update the values in the database
        Helper::upsert($this->db, 'documents', $dataDocument, $this->getPrimaryKey('documents'));

        if ($typeSpecificTable) {
            Helper::upsert($this->db, $typeSpecificTable, $dataTypeSpecific, $this->getPrimaryKey($typeSpecificTable));
        }

        $this->updateLocks();
    }

    /**
     * Delete the row from the database. (based on the model id)
     *
     * @throws Exception
     */
    public function delete(): void
    {
        $this->db->delete('documents', ['id' => $this->model->getId()]);
    }

    /**
     * Update document workspaces.
     *
     * @throws Exception
     */
    public function updateWorkspaces(): void
    {
        $this->db->update('users_workspaces_document', [
            'cpath' => $this->model->getRealFullPath(),
        ], [
            'cid' => $this->model->getId(),
        ]);
    }

    /**
     * Updates children path in order to the old document path specified in the $oldPath parameter.
     *
     * @internal
     */
    public function updateChildPaths(string $oldPath): array
    {
        //get documents to empty their cache
        $documents = $this->db->fetchAllAssociative(
            'SELECT id, CONCAT(`path`,`key`) as `path` FROM documents WHERE `path` LIKE ?',
            [Helper::escapeLike($oldPath) . '%']
        );

        $userId = '0';
        if ($user = \OpenDxp\Tool\Admin::getCurrentUser()) {
            $userId = $user->getId();
        }

        $newPath = $this->model->getRealFullPath();

        //update documents child paths
        // we don't update the modification date here, as this can have side-effects when there's an unpublished version for an element
        $this->db->executeStatement(
            'UPDATE documents SET `path` = REPLACE(`path`, ?, ?), userModification = ? WHERE `path` LIKE ?',
            [$oldPath . '/', $newPath . '/', $userId, Helper::escapeLike($oldPath) . '/%']
        );

        //update documents child permission paths
        $this->db->executeStatement(
            'UPDATE users_workspaces_document SET cpath = REPLACE(cpath, ?, ?) WHERE cpath LIKE ?',
            [$oldPath . '/', $newPath . '/', Helper::escapeLike($oldPath) . '/%']
        );

        //update documents child properties paths
        $this->db->executeStatement(
            'UPDATE properties SET cpath = REPLACE(cpath, ?, ?) WHERE cpath LIKE ?',
            [$oldPath . '/', $newPath . '/', Helper::escapeLike($oldPath) . '/%']
        );

        return $documents;
    }

    /**
     * Returns the current full document path from the database.
     */
    public function getCurrentFullPath(): ?string
    {
        $path = null;

        try {
            $path = $this->db->fetchOne(
                'SELECT CONCAT(`path`,`key`) as `path` FROM documents WHERE id = ?',
                [$this->model->getId()]
            );
        } catch (Exception) {
            Logger::error('could not  get current document path from DB');
        }

        return $path;
    }

    public function getVersionCountForUpdate(): int
    {
        if (!$this->model->getId()) {
            return 0;
        }

        $versionCount = (int) $this->db->fetchOne(
            'SELECT versionCount FROM documents WHERE id = ? FOR UPDATE',
            [$this->model->getId()]
        );

        if ($this->model instanceof PageSnippet) {
            $versionCount2 = (int) $this->db->fetchOne(
                'SELECT MAX(versionCount) FROM versions WHERE cid = ? AND ctype = "document"',
                [$this->model->getId()]
            );
            $versionCount = max($versionCount, $versionCount2);
        }

        return (int) $versionCount;
    }

    /**
     * Returns properties for the object from the database
     *
     * @throws Exception
     */
    public function getProperties(bool $onlyInherited = false, bool $onlyDirect = false): array
    {
        $properties = [];

        if ($onlyDirect) {
            $propertiesRaw =
                $this->db->fetchAllAssociative(
                    'SELECT * FROM properties WHERE cid = ? AND ctype="document"',
                    [$this->model->getId()]
                );
        } else {
            $parentIds = $this->getParentIds();
            $propertiesRaw =
                $this->db->fetchAllAssociative(
                    'SELECT * FROM properties WHERE ((cid IN (?) AND inheritable = 1) OR cid = ?) AND ctype="document"',
                    [$parentIds, $this->model->getId()],
                    [ArrayParameterType::INTEGER, ParameterType::INTEGER]
                );
        }

        // because this should be faster than mysql
        usort($propertiesRaw, fn ($left, $right) => strcmp($left['cpath'], $right['cpath']));

        foreach ($propertiesRaw as $propertyRaw) {
            try {
                $id = $this->model->getId();
                $property = new Model\Property();
                $property->setType($propertyRaw['type']);
                if ($id !== null) {
                    $property->setCid($id);
                }
                $property->setName($propertyRaw['name']);
                $property->setCtype('document');
                $property->setDataFromResource($propertyRaw['data']);
                $property->setInherited(true);
                if ($propertyRaw['cid'] == $id) {
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
            } catch (Exception) {
                Logger::error(
                    "can't add property " . $propertyRaw['name'] . ' to document ' . $this->model->getRealFullPath()
                );
            }
        }

        return $properties;
    }

    /**
     * Deletes all object properties from the database.
     */
    public function deleteAllProperties(): void
    {
        $this->db->delete('properties', ['cid' => $this->model->getId(), 'ctype' => 'document']);
    }

    /**
     * Quick check if there are children.
     */
    public function hasChildren(?bool $includingUnpublished = null, ?User $user = null): bool
    {
        if (!$this->model->getId()) {
            return false;
        }

        $sql = 'SELECT id FROM documents d WHERE parentId = ? ';

        if ($user && !$user->isAdmin()) {
            $userIds = array_map('intval', $user->getRoles());
            $currentUserId = $user->getId();
            $userIds[] = $currentUserId;

            $inheritedPermission = $this->isInheritingPermission('list', $userIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_document uwd WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(d.path,d.`key`),cpath)=1 AND
                NOT EXISTS(SELECT list FROM users_workspaces_document WHERE userId =' . (int) $currentUserId . '  AND list=0 AND cpath = uwd.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = id AND list=0)';

            $sql .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        $includingUnpublished ??= !Model\Document::doHideUnpublished();
        if (!$includingUnpublished) {
            $sql .= ' AND published = 1';
        }

        $sql .= ' LIMIT 1';

        $c = $this->db->fetchOne($sql, [$this->model->getId()]);

        return (bool)$c;
    }

    /**
     * Returns the amount of children (not recursively),
     */
    public function getChildAmount(?User $user = null): int
    {
        if (!$this->model->getId()) {
            return 0;
        }
        $sql = 'SELECT count(*) FROM documents d WHERE parentId = ? ';
        if ($user && !$user->isAdmin()) {
            $userIds = array_map('intval', $user->getRoles());
            $currentUserId = $user->getId();
            $userIds[] = $currentUserId;

            $inheritedPermission = $this->isInheritingPermission('list', $userIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_document uwd WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(d.path,d.`key`),cpath)=1 AND
                NOT EXISTS(SELECT list FROM users_workspaces_document WHERE userId =' . (int) $currentUserId . '  AND list=0 AND cpath = uwd.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = id AND list=0)';

            $sql .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        return (int) $this->db->fetchOne($sql, [$this->model->getId()]);
    }

    /**
     * Checks if the document has siblings
     */
    public function hasSiblings(?bool $includingUnpublished = null): bool
    {
        if (!$this->model->getParentId()) {
            return false;
        }

        $sql = 'SELECT id FROM documents WHERE parentId = ?';
        $params = [$this->model->getParentId()];

        if ($this->model->getId()) {
            $sql .= ' AND id != ?';
            $params[] = $this->model->getId();
        }

        $includingUnpublished ??= !Model\Document::doHideUnpublished();
        if (!$includingUnpublished) {
            $sql .= ' AND published = 1';
        }

        $sql .= ' LIMIT 1';

        $c = $this->db->fetchOne($sql, $params);

        return (bool)$c;
    }

    /**
     * Checks if the document is locked.
     *
     * @throws Exception
     */
    public function isLocked(): bool
    {
        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne(
            'SELECT tree_locks.id FROM tree_locks
                INNER JOIN documents ON tree_locks.id = documents.id
                WHERE documents.path LIKE ? AND tree_locks.type = "document" AND tree_locks.locked IS NOT NULL AND tree_locks.locked != "" LIMIT 1',
            [Helper::escapeLike($this->model->getRealFullPath()) . '/%']
        );

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne(
            'SELECT id FROM tree_locks WHERE id IN (?) AND `type` = "document" AND locked = "propagate" LIMIT 1',
            [$parentIds],
            [ArrayParameterType::INTEGER]
        );

        return $inhertitedLocks > 0;
    }

    /**
     * Update the lock value for the document.
     *
     * @throws Exception
     */
    public function updateLocks(): void
    {
        $this->db->delete('tree_locks', ['id' => $this->model->getId(), 'type' => 'document']);
        if ($this->model->getLocked()) {
            $this->db->insert('tree_locks', [
                'id' => $this->model->getId(),
                'type' => 'document',
                'locked' => $this->model->getLocked(),
            ]);
        }
    }

    /**
     * Deletes locks from the document and its children.
     */
    public function unlockPropagate(): array
    {
        $lockIds = $this->db->fetchFirstColumn(
            'SELECT id FROM documents WHERE `path` LIKE ? OR id = ?',
            [Helper::escapeLike($this->model->getRealFullPath()) . '/%', $this->model->getId()]
        );

        $this->db->executeStatement(
            'DELETE FROM tree_locks WHERE `type` = "document" AND id IN (?)',
            [$lockIds],
            [ArrayParameterType::INTEGER]
        );

        return $lockIds;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function isInheritingPermission(string $type, array $userIds): int
    {
        return $this->InheritingPermission($type, $userIds, 'document');
    }

    /**
     * Checks if the action is allowed.
     */
    public function isAllowed(string $type, User $user): bool
    {
        // collect properties via parent - ids
        $parentIds = [1];

        $obj = $this->model->getParent();
        if ($obj) {
            while ($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        if ($id = $this->model->getId()) {
            $parentIds[] = $id;
        }

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne(
                'SELECT ' . $this->db->quoteIdentifier($type) . ' FROM users_workspaces_document WHERE cid IN (?) AND userId IN (?) ORDER BY LENGTH(cpath) DESC, FIELD(userId, ?) DESC, ' . $this->db->quoteIdentifier($type) . ' DESC LIMIT 1',
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
                    'SELECT list FROM users_workspaces_document WHERE cpath LIKE ? AND userId IN (?) AND list = 1 LIMIT 1',
                    [Helper::escapeLike($path) . '%', $userIds],
                    [ParameterType::STRING, ArrayParameterType::INTEGER]
                );
                if ($permissionsChildren) {
                    return true;
                }
            }
        } catch (Exception) {
            Logger::warn('Unable to get permission ' . $type . ' for document ' . $this->model->getId());
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
        return $this->permissionByTypes($columns, $user, 'document');
    }

    /**
     * Save the document index.
     */
    public function saveIndex(int $index): void
    {
        $this->db->update('documents', [
            $this->db->quoteIdentifier('index') => $index,
        ], [
            'id' => $this->model->getId(),
        ]);
    }

    /**
     * Fetches the maximum index value from siblings.
     */
    public function getNextIndex(): int
    {
        $index = $this->db->fetchOne('SELECT MAX(`index`) FROM documents WHERE parentId = ?', [$this->model->getParentId()]);
        $index++;

        return $index;
    }

    public function __isBasedOnLatestData(): bool
    {
        $data = $this->db->fetchAssociative('SELECT modificationDate,versionCount FROM documents WHERE id = ?', [$this->model->getId()]);

        return $data['modificationDate'] == $this->model->__getDataVersionTimestamp() && $data['versionCount'] == $this->model->getVersionCount();
    }
}
