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

namespace OpenDxp\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing;

use Exception;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Classificationstore group configs for the specified parameters, returns an array of config elements
     *
     */
    public function load(): array
    {
        $condition = $this->getCondition();
        $condition = $condition ? $condition . ' AND ' : ' where ';
        $condition .= DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS
            . '.groupId = ' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '.id';

        $sql = 'SELECT * FROM ' . DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS
            . ',' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS
            . $condition . $this->getOrder() . $this->getOffsetLimit();

        $data = $this->db->fetchAllAssociative($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $configData = [];
        foreach ($data as $dataItem) {
            $entry = new DataObject\Classificationstore\CollectionGroupRelation();
            $resource = $entry->getDao();
            $resource->assignVariablesToModel($dataItem);

            $configData[] = $entry;
        }

        $this->model->setList($configData);

        return $configData;
    }

    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS . ' '. $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
