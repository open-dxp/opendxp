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

namespace OpenDxp\Model\Notification\Listing;

use Doctrine\DBAL\Exception;
use OpenDxp\Model\Listing\Dao\AbstractDao;
use OpenDxp\Model\Notification;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Notification\Listing $model
 */
class Dao extends AbstractDao
{
    const DB_TABLE_NAME = 'notifications';

    public function count(): int
    {
        $sql = sprintf('SELECT COUNT(*) AS num FROM `%s`%s', static::DB_TABLE_NAME, $this->getCondition());

        try {
            $count = (int) $this->db->fetchOne($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (\Exception) {
            $count = 0;
        }

        return $count;
    }

    public function getTotalCount(): int
    {
        return $this->count();
    }

    /**
     *
     * @throws Exception
     */
    public function load(): array
    {
        $notifications = [];
        $sql = sprintf(
            'SELECT id FROM `%s`%s%s%s',
            static::DB_TABLE_NAME,
            $this->getCondition(),
            $this->getOrder(),
            $this->getOffsetLimit()
        );

        $ids = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        foreach ($ids as $id) {
            $notification = Notification::getById((int) $id);

            if ($notification instanceof Notification) {
                $notifications[] = $notification;
            }
        }

        $this->model->setNotifications($notifications);

        return $notifications;
    }
}
