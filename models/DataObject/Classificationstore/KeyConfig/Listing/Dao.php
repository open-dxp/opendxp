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

namespace OpenDxp\Model\DataObject\Classificationstore\KeyConfig\Listing;

use Exception;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\Classificationstore\KeyConfig\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Classificationstore key configs for the specified parameters, returns an array of config elements
     *
     */
    public function load(): array
    {
        $sql = 'SELECT * FROM ' . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchAllAssociative($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $configList = [];
        foreach ($configsData as $keyConfigData) {
            $keyConfig = new DataObject\Classificationstore\KeyConfig();
            $keyConfigData['enabled'] = (bool)$keyConfigData['enabled'];
            $keyConfig->setValues($keyConfigData);
            $configList[] = $keyConfig;
        }

        $this->model->setList($configList);

        return $configList;
    }

    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . ' '. $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }

    #[\Override]
    protected function getCondition(): string
    {
        $condition = $this->model->getIncludeDisabled() ? '(enabled is null or enabled = 0)' : 'enabled = 1';

        $cond = $this->model->getCondition();
        if ($cond) {
            $condition = $condition . ' AND (' . $cond . ')';
        }

        return ' WHERE ' . $condition . ' ';
    }
}
