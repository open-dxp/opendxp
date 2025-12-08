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

namespace OpenDxp\Model\Version\Listing;

use Exception;
use OpenDxp\Model;
use Override;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Version\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    #[Override]
    public function getCondition(): string
    {
        $condition = parent::getCondition();
        if ($this->model->isLoadAutoSave() == false) {
            if (trim($condition)) {
                $condition .= ' AND autoSave = 0';
            } else {
                $condition = ' WHERE autoSave = 0';
            }
        }

        return $condition;
    }

    /**
     * Loads a list of versions for the specicified parameters, returns an array of Version elements
     *
     * @return Model\Version[]
     */
    public function load(): array
    {
        $versions = [];
        $data = $this->loadIdList();

        foreach ($data as $id) {
            $versions[] = Model\Version::getById($id);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $versionIds = $this->db->fetchFirstColumn('SELECT id FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $versionIds);
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM versions ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
