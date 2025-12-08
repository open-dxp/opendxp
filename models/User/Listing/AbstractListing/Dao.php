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

namespace OpenDxp\Model\User\Listing\AbstractListing;

use Exception;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Model\User\Listing\AbstractListing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of users for the specified parameters, returns an array of User elements
     *
     */
    public function load(): array
    {
        $items = [];
        $usersData = $this->db->fetchAllAssociative('SELECT id,type FROM users' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($usersData as $userData) {
            $className = Model\User\Service::getClassNameForType($userData['type']);
            $item = $className::getById($userData['id']);
            if ($item) {
                $items[] = $item;
            }
        }

        $this->model->setItems($items);

        return $items;
    }

    #[\Override]
    protected function getCondition(): string
    {
        $condition = parent::getCondition();
        if (!empty($condition)) {
            $condition .= ' AND ';
        } else {
            $condition = ' WHERE ';
        }

        $types = [$this->model->getType(), $this->model->getType() . 'folder'];

        return $condition . ("id > 0 AND `type` IN ('" . implode("','", $types) . "')");
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM users ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
