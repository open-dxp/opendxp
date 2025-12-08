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

namespace OpenDxp\Model\Document\DocType;

use Exception;
use OpenDxp\Config;
use OpenDxp\Model;
use Override;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Document\DocType $model
 */
class Dao extends Model\Dao\OpenDxpLocationAwareConfigDao
{
    private const string CONFIG_KEY = 'document_types';

    #[Override]
    public function configure(): void
    {
        $config = Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['documents']['doc_types']['definitions'],
            'settingsStoreScope' => 'opendxp_document_types',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     * Get the data for the object from database for the given id
     *
     *
     * @throws Exception
     */
    public function getById(?string $id = null): void
    {
        $data = null;
        if ($id !== null) {
            $data = $this->getDataByName($id);
        }

        if (!$data) {
            throw new Model\Exception\NotFoundException(sprintf(
                'Document Type with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }

        $data['id'] = $id;
        $this->assignVariablesToModel($data);
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
        $allowedProperties = ['name', 'group', 'controller',
            'template', 'type', 'priority', 'creationDate', 'modificationDate', 'staticGeneratorEnabled', ];

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
                'documents' => [
                    'doc_types' => [
                        'definitions' => [
                            $id => $data,
                        ],
                    ],
                ],
            ],
        ];
    }
}
