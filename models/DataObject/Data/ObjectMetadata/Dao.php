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

namespace OpenDxp\Model\DataObject\Data\ObjectMetadata;

use OpenDxp\Db\Helper;
use OpenDxp\Model\DataObject;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\Data\ObjectMetadata $model
 */
class Dao extends DataObject\Data\AbstractMetadata\Dao
{
    use DataObject\ClassDefinition\Helper\Dao;

    protected ?array $tableDefinitions = null;

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

    #[\Override]
    protected function getTablename(DataObject\Concrete $object): string
    {
        return 'object_metadata_' . $object->getClassId();
    }

    public function load(DataObject\Concrete $source, int $destinationId, string $fieldname, string $ownertype, string $ownername, string $position, int $index, string $destinationType = 'object'): ?DataObject\Data\ObjectMetadata
    {
        $typeQuery = " AND (`type` = 'object' or `type` = '')";

        $query = 'SELECT * FROM ' . $this->getTablename($source) . ' WHERE id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ? and `index` = ? ' . $typeQuery;
        $dataRaw = $this->db->fetchAllAssociative($query, [$source->getId(), $destinationId, $fieldname, $ownertype, $ownername, $position, $index]);
        if ($dataRaw !== []) {
            $this->model->setObjectId($destinationId);
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
