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

namespace OpenDxp\Model\Tool\Email\Blocklist\Listing;

use Exception;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Tool\Email\Blocklist\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Tool\Email\Blocklist elements
     *
     */
    public function load(): array
    {
        $addressData = $this->db->fetchFirstColumn('SELECT address FROM email_blocklist' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $addresses = [];
        foreach ($addressData as $data) {
            if ($address = Model\Tool\Email\Blocklist::getByAddress($data)) {
                $addresses[] = $address;
            }
        }

        $this->model->setItems($addresses);

        return $addresses;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM email_blocklist ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
