<?php
declare(strict_types=1);

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

namespace OpenDxp\Model\DataObject\SelectOptions\Config;

use InvalidArgumentException;
use OpenDxp\Config;
use OpenDxp\Model;
use Override;
use RuntimeException;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\SelectOptions\Config $model
 */
class Dao extends Model\Dao\OpenDxpLocationAwareConfigDao
{
    private const string CONFIG_KEY = 'select_options';

    #[Override]
    public function configure(): void
    {
        $config = Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['objects']['select_options']['definitions'],
            'settingsStoreScope' => 'opendxp_select_options',
            'storageConfig' => $storageConfig,
        ]);
    }

    public function getById(?string $id = null): void
    {
        if ($id !== null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());
        if ($data && $id !== null) {
            $data['id'] = $id;
        }

        if (!$data) {
            throw new Model\Exception\NotFoundException(
                sprintf(
                    'Select options with ID "%s" does not exist.',
                    $this->model->getId()
                ),
                1678366154585
            );
        }

        $selectOptionsData = $data[Model\DataObject\SelectOptions\Config::PROPERTY_SELECT_OPTIONS] ?? [];
        $this->model->setSelectOptionsFromData($selectOptionsData);

        unset($data[Model\DataObject\SelectOptions\Config::PROPERTY_SELECT_OPTIONS]);
        $this->assignVariablesToModel($data);
    }

    public function exists(string $name): bool
    {
        return (bool) $this->getDataByName($this->model->getId());
    }

    public function save(): void
    {
        $this->validateId();

        $this->saveConfiguration();
        $this->model->generateEnumFiles();
    }

    protected function saveConfiguration(): void
    {
        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = [
            Model\DataObject\SelectOptions\Config::PROPERTY_ID,
            Model\DataObject\SelectOptions\Config::PROPERTY_USE_TRAITS,
            Model\DataObject\SelectOptions\Config::PROPERTY_IMPLEMENTS_INTERFACES,
            Model\DataObject\SelectOptions\Config::PROPERTY_GROUP,
        ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }

        $data[Model\DataObject\SelectOptions\Config::PROPERTY_SELECT_OPTIONS] = $this->model->getSelectOptionsAsData();

        $this->saveData($this->model->getId(), $data);
    }

    protected function validateId(): void
    {
        $id = $this->model->getId();
        if (empty($id)) {
            throw new InvalidArgumentException('A select options definition needs an ID to be saved!', 1676639722696);
        }

        if (!preg_match('/[A-Z][a-zA-Z0-9]+/', $id)) {
            throw new InvalidArgumentException('Invalid ID: Must start with capital letter, followed by alphanumeric characters', 1676639634486);
        }
    }

    #[Override]
    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'opendxp' => [
                'objects' => [
                    'select_options' => [
                        'definitions' => [
                            $id => $data,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function delete(): void
    {
        $this->reportFieldsUsedIn();
        $this->deleteData($this->model->getId());
        @unlink($this->model->getPhpClassFile());
    }

    protected function reportFieldsUsedIn(): void
    {
        $fieldsUsedIn = $this->model->getFieldsUsedIn();
        if (empty($fieldsUsedIn)) {
            return;
        }

        $report = [];
        foreach ($fieldsUsedIn as $className => $fieldNames) {
            $report[] = $className . ': ' . implode(', ', $fieldNames);
        }

        throw new RuntimeException(
            'Select options are still used by ' . implode(' / ', $report),
            1676887977650
        );
    }
}
