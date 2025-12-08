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

namespace OpenDxp\Model\DataObject\Fieldcollection\Data;

use Exception;
use OpenDxp\Db\Helper;
use OpenDxp\Model;
use OpenDxp\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use OpenDxp\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Exception
     */
    public function save(Model\DataObject\Concrete $object, array $params = [], bool|array $saveRelationalData = true): void
    {
        $tableName = $this->model->getDefinition()->getTableName($object->getClass());
        $data = [
            'id' => $object->getId(),
            'index' => $this->model->getIndex(),
            'fieldname' => $this->model->getFieldname(),
        ];

        foreach ($this->model->getDefinition()->getFieldDefinitions() as $fieldName => $fd) {
            $getter = 'get' . ucfirst($fieldName);

            if ($fd instanceof CustomResourcePersistingInterface) {

                if (!$fd instanceof Model\DataObject\ClassDefinition\Data\Localizedfields && $fd->supportsDirtyDetection() && !$saveRelationalData) {
                    continue;
                }

                // for fieldtypes which have their own save algorithm eg. relational data types, ...
                $index = $this->model->getIndex();
                $params = [...$params, 'saveRelationalData' => $saveRelationalData, 'context' => [
                    'containerType' => 'fieldcollection',
                    'containerKey' => $this->model->getType(),
                    'fieldname' => $this->model->getFieldname(),
                    'index' => $index,
                ]];

                if ($fd instanceof Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations
                            && ($params['saveRelationalData']['saveFieldcollectionRelations'] ?? false)) {
                    $params['forceSave'] = true;
                }

                $fd->save(
                    $this->model, $params
                );
            }
            if ($fd instanceof ResourcePersistenceAwareInterface) {
                $fieldDefinitionParams = [
                    'owner' => $this->model, //\OpenDxp\Model\DataObject\Fieldcollection\Data\Dao
                    'fieldname' => $fd->getName(),
                ];
                if (is_array($fd->getColumnType())) {
                    $insertDataArray = $fd->getDataForResource($this->model->$getter(), $object, $fieldDefinitionParams);
                    $data = [...$data, ...$insertDataArray];
                    $this->model->set($fieldName, $fd->getDataFromResource($insertDataArray, $object, $fieldDefinitionParams));
                } else {
                    $insertData = $fd->getDataForResource($this->model->$getter(), $object, $fieldDefinitionParams);
                    $data[$fd->getName()] = $insertData;
                    $this->model->set($fieldName, $fd->getDataFromResource($insertData, $object, $fieldDefinitionParams));
                }

                $this->model->markFieldDirty($fieldName, false);
            }
        }

        $this->db->insert($tableName, Helper::quoteDataIdentifiers($this->db, $data));
    }
}
