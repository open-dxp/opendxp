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

namespace OpenDxp\Model\User\UserRole;

use Exception;
use OpenDxp\Model;
use OpenDxp\Model\Element;

/**
 * @internal
 *
 * @property \OpenDxp\Model\User\UserRole $model
 */
class Dao extends Model\User\AbstractUser\Dao
{
    /**
     *
     * @throws Exception
     */
    #[\Override]
    public function getById(int $id): void
    {
        parent::getById($id);

        if (in_array($this->model->getType(), ['user', 'role'])) {
            $this->loadWorkspaces();
        }
    }

    /**
     *
     * @throws Exception
     */
    #[\Override]
    public function getByName(string $name): void
    {
        parent::getByName($name);

        if (in_array($this->model->getType(), ['user', 'role'])) {
            $this->loadWorkspaces();
        }
    }

    public function loadWorkspaces(): void
    {
        $types = ['asset', 'document', 'object'];

        foreach ($types as $type) {
            $workspaces = [];
            $baseClassName = Element\Service::getBaseClassNameForElement($type);
            $className = '\\OpenDxp\\Model\\User\\Workspace\\' . $baseClassName;
            $result = $this->db->fetchAllAssociative('SELECT * FROM users_workspaces_' . $type . ' WHERE userId = ?', [$this->model->getId()]);
            foreach ($result as $row) {
                $workspace = new $className();
                $row['list'] = (bool)$row['list'];
                $row['view'] = (bool)$row['view'];
                $row['publish'] = (bool)$row['publish'];
                $row['delete'] = (bool)$row['delete'];
                $row['rename'] = (bool)$row['rename'];
                $row['create'] = (bool)$row['create'];
                $row['settings'] = (bool)$row['settings'];
                $row['versions'] = (bool)$row['versions'];
                $row['properties'] = (bool)$row['properties'];
                if ($type === 'document' || $type === 'object') {
                    $row['save'] = (bool)$row['save'];
                    $row['unpublish'] = (bool)$row['unpublish'];
                }
                $workspace->setValues($row, true);
                $workspaces[] = $workspace;
            }

            $this->model->{'setWorkspaces' . ucfirst($type)}($workspaces);
        }
    }

    public function emptyWorkspaces(): void
    {
        $this->db->delete('users_workspaces_asset', ['userId' => $this->model->getId()]);
        $this->db->delete('users_workspaces_document', ['userId' => $this->model->getId()]);
        $this->db->delete('users_workspaces_object', ['userId' => $this->model->getId()]);
    }
}
