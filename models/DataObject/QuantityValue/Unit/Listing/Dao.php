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

namespace OpenDxp\Model\DataObject\QuantityValue\Unit\Listing;

use Exception;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\QuantityValue\Unit\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    public function load(): array
    {
        $units = [];

        $unitConfigs = $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\QuantityValue\Unit\Dao::TABLE_NAME .
            $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($unitConfigs as $unitConfig) {
            $unit = new DataObject\QuantityValue\Unit();
            $unit->setValues($unitConfig, true);
            $units[] = $unit;
        }

        $this->model->setUnits($units);

        return $units;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM '.DataObject\QuantityValue\Unit\Dao::TABLE_NAME.' ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
