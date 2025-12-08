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

namespace OpenDxp\Model\DataObject\Data\ElementMetadata;

use OpenDxp\Db\Helper;
use OpenDxp\Model\DataObject;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\Data\ElementMetadata $model
 */
class Dao extends DataObject\Data\AbstractMetadata\Dao
{
    public function save(DataObject\Concrete $object, string $ownertype, string $ownername, string $position, int $index, string $type = 'object'): void
    {
        $table = $this->getTablename($object);

        $dataTemplate = ['id' => $object->getId(),
            'dest_id' => $this->model->getElement()->getId(),
            'fieldname' => $this->model->getFieldname(),
            'ownertype' => $ownertype,
            'ownername' => $ownername ?: '',
            'index' => $index ?: '0',
            'position' => $position ?: '0',
            'type' => $type ?: 'object', ];

        foreach ($this->model->getColumns() as $column) {
            $getter = 'get' . ucfirst($column);
            $data = $dataTemplate;
            $data['column'] = $column;
            $data['data'] = $this->model->$getter();
            Helper::upsert($this->db, $table, $data, parent::UNIQUE_COLUMNS);
        }
    }

    public function load(DataObject\Concrete $source, int $destinationId, string $fieldname, string $ownertype, string $ownername, string $position, int $index, string $destinationType = 'object'): ?DataObject\Data\ElementMetadata
    {
        if ($destinationType === 'object') {
            $typeQuery = " AND (`type` = 'object' or `type` = '')";
        } else {
            $typeQuery = ' AND `type` = ' . $this->db->quote($destinationType);
        }

        $dataRaw = $this->db->fetchAllAssociative('SELECT * FROM ' .
            $this->getTablename($source) . ' WHERE ' . $this->getTablename($source) .'.id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ? and `index` = ? ' . $typeQuery, [$source->getId(), $destinationId, $fieldname, $ownertype, $ownername, $position, $index]);
        if ($dataRaw !== []) {
            $this->model->setElementTypeAndId($destinationType, $destinationId);
            $this->model->setFieldname($fieldname);
            $columns = $this->model->getColumns();
            foreach ($dataRaw as $row) {
                if (in_arrayi($row['column'], $columns)) {
                    $setter = 'set' . ucfirst($row['column']);
                    $this->model->$setter($row['data']);
                }
            }

            return $this->model;
        }

        return null;
    }
}
