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

namespace OpenDxp\Model\Tool\SettingsStore;

use Exception;
use OpenDxp\Db\Helper;
use OpenDxp\Model;
use OpenDxp\Model\Tool\SettingsStore;

/**
 * @internal
 *
 * @property SettingsStore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME = 'settings_store';

    public function set(string $id, float|bool|int|string $data, string $type = SettingsStore::TYPE_STRING, ?string $scope = null): bool
    {
        try {
            Helper::upsert($this->db, self::TABLE_NAME, [
                'id' => $id,
                'data' => $data,
                'scope' => (string) $scope,
                'type' => $type,
            ], $this->getPrimaryKey(self::TABLE_NAME));

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function delete(string $id, ?string $scope = null): int|string
    {
        return $this->db->delete(self::TABLE_NAME, [
            'id' => $id,
            'scope' => (string) $scope,
        ]);
    }

    /**
     * @throws Model\Exception\NotFoundException
     */
    public function getById(string $id, ?string $scope = null): void
    {
        $item = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = :id AND scope = :scope', [
            'id' => $id,
            'scope' => (string) $scope,
        ]);

        if (!$item) {
            throw new Model\Exception\NotFoundException('settings store with id ' . $id . ' and scope ' . $scope . ' not found');
        }

        $this->assignVariablesToModel($item);

        $data = $item['data'] ?? null;
        $this->model->setData($data);
    }

    public function getIdsByScope(string $scope): array
    {
        return $this->db->fetchFirstColumn('SELECT id FROM ' . self::TABLE_NAME . ' WHERE scope = ?', [$scope]);
    }
}
