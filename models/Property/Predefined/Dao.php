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

namespace OpenDxp\Model\Property\Predefined;

use Exception;
use OpenDxp\Config;
use OpenDxp\Model;
use Override;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Property\Predefined $model
 */
class Dao extends Model\Dao\OpenDxpLocationAwareConfigDao
{
    private const string CONFIG_KEY = 'predefined_properties';

    #[Override]
    public function configure(): void
    {
        $config = Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['properties']['predefined']['definitions'],
            'settingsStoreScope' => 'opendxp_predefined_properties',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(?string $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByKey(?string $key = null): void
    {
        if ($key != null) {
            $this->model->setKey($key);
        }
        $key = $this->model->getKey();

        $list = new Listing();
        /** @var Model\Property\Predefined[] $properties */
        $properties = array_values(array_filter($list->getProperties(), fn ($item) => $item->getKey() == $key
        ));

        if (count($properties) && $properties[0]->getId()) {
            $this->assignVariablesToModel($properties[0]->getObjectVars());
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with key "%s" does not exist.',
                $this->model->getKey()
            ));
        }
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->model->setId((string)Uid::v4());
        }
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'description', 'key', 'type', 'data',
            'config', 'ctype', 'inheritable', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->saveData($this->model->getId(), $data);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getId());
    }

    #[Override]
    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'opendxp' => [
                'properties' => [
                    'predefined' => [
                        'definitions' => [
                            $id => $data,
                        ],
                    ],
                ],
            ],
        ];
    }
}
