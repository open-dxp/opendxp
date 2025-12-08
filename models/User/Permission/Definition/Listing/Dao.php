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

namespace OpenDxp\Model\User\Permission\Definition\Listing;

use Exception;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Model\User\Permission\Definition\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of definitions for the specicified parameters, returns an array of User\Permission\Definition elements
     *
     */
    public function load(): array
    {
        $definitions = [];
        $definitionsData = $this->db->fetchAllAssociative('SELECT * FROM users_permission_definitions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($definitionsData as $definitionData) {
            $definition = new Model\User\Permission\Definition($definitionData);
            $definitions[] = $definition;
        }

        $this->model->setDefinitions($definitions);

        return $definitions;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM users_permission_definitions ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
