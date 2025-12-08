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

namespace OpenDxp\Model\Tool\TmpStore;

use Exception;
use OpenDxp\Db\Helper;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Tool\TmpStore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function add(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        try {
            $serialized = false;
            if (is_object($data) || is_array($data)) {
                $serialized = true;
                $data = serialize($data);
            }

            Helper::upsert($this->db, 'tmp_store', [
                'id' => $id,
                'data' => $data,
                'tag' => $tag,
                'date' => time(),
                'expiryDate' => (time() + $lifetime),
                'serialized' => (int) $serialized,
            ], $this->getPrimaryKey('tmp_store'));

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function delete(string $id): void
    {
        $this->db->delete('tmp_store', ['id' => $id]);
    }

    public function getById(string $id): bool
    {
        $item = $this->db->fetchAssociative('SELECT * FROM tmp_store WHERE id = ?', [$id]);

        if ($item) {
            if ($item['serialized']) {
                $item['data'] = unserialize($item['data']);
            }

            $item['serialized'] = (bool)$item['serialized'];
            $this->assignVariablesToModel($item);

            return true;
        }

        return false;
    }

    public function getIdsByTag(string $tag): array
    {
        return $this->db->fetchFirstColumn('SELECT id FROM tmp_store WHERE tag = ?', [$tag]);
    }
}
