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

namespace OpenDxp\Bundle\CustomReportsBundle\Tool\Config;

use Exception;
use OpenDxp;
use OpenDxp\Model;
use Override;

/**
 * @internal
 *
 * @property \OpenDxp\Bundle\CustomReportsBundle\Tool\Config $model
 */
class Dao extends Model\Dao\OpenDxpLocationAwareConfigDao
{
    private const string CONFIG_KEY = 'custom_reports';

    #[Override]
    public function configure(): void
    {
        $config = OpenDxp::getContainer()->getParameter('opendxp_custom_reports.config_location');
        $definitions = OpenDxp::getContainer()->getParameter('opendxp_custom_reports.definitions');

        $storageConfig = $config[self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $definitions,
            'settingsStoreScope' => 'opendxp_custom_reports',
            'storageConfig' => $storageConfig,
            'legacyConfigFile' => 'custom-reports.php',
        ]);
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName(?string $id = null): void
    {
        if ($id != null) {
            $this->model->setName($id);
        }

        $data = $this->getDataByName($this->model->getName());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
            $this->model->setName($data['id']);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Custom report config with name "%s" does not exist.',
                $this->model->getName()
            ));
        }
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'sql', 'dataSourceConfig', 'columnConfiguration', 'niceName', 'group', 'xAxis',
            'groupIconClass', 'iconClass', 'reportClass', 'creationDate', 'modificationDate', 'menuShortcut', 'chartType', 'pieColumn',
            'pieLabelColumn', 'yAxis', 'shareGlobally', 'sharedUserNames', 'sharedRoleNames', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->saveData($this->model->getName(), $data);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getName());
    }

    #[Override]
    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'opendxp_custom_reports' => [
                'definitions' => [
                    $id => $data,
                ],
            ],
        ];
    }
}
