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

namespace OpenDxp\Model\Tool\Email\Log\Listing;

use Exception;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Tool\Email\Log\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Email_Log for the specified parameters, returns an array of Email_Log elements
     *
     */
    public function load(): array
    {
        $emailLogs = $this->db->fetchFirstColumn('SELECT id FROM email_log' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $emailLogsArray = [];
        foreach ($emailLogs as $log) {
            $emailLogsArray[] = Model\Tool\Email\Log::getById($log);
        }
        $this->model->setEmailLogs($emailLogsArray);

        return $emailLogsArray;
    }

    /**
     * Returns the db entries from email_log by the specified parameters
     *
     */
    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM email_log ' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    /**
     * Returns the total amount of Email_Log entries
     *
     */
    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM email_log ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
