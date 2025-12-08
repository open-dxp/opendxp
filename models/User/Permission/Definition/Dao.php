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

namespace OpenDxp\Model\User\Permission\Definition;

use Exception;
use OpenDxp\Db\Helper;
use OpenDxp\Logger;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Model\User\Permission\Definition $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function save(): void
    {
        try {
            Helper::upsert($this->db, 'users_permission_definitions', [
                'key' => $this->model->getKey(),
                'category' => $this->model->getCategory() ?: '',
            ], $this->getPrimaryKey('users_permission_definitions'));
        } catch (Exception $e) {
            Logger::warn((string) $e);
        }
    }
}
