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

namespace OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Model;
use OpenDxp\Model\Exception\NotFoundException;
use Override;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property Staticroute $model
 */
class Dao extends Model\Dao\OpenDxpLocationAwareConfigDao
{
    private const string CONFIG_KEY = 'staticroutes';

    #[Override]
    public function configure(): void
    {
        $config = OpenDxp::getContainer()->getParameter('opendxp_static_routes.config_location');
        $definitions = OpenDxp::getContainer()->getParameter('opendxp_static_routes.definitions');

        $storageConfig = $config[self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $definitions,
            'settingsStoreScope' => 'opendxp_staticroutes',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getId());
    }

    /**
     * Get the data for the object from database for the given id
     *
     *
     * @throws NotFoundException
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
                'Static Route with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     *
     * @throws NotFoundException
     */
    public function getByName(?string $name = null, ?int $siteId = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();

        $listing = new Listing();
        $totalList = $listing->load();

        $data = array_filter($totalList, function (Staticroute $row) use ($name, $siteId) {
            if ($row->getName() != $name) {
                return false;
            }

            return empty($row->getSiteId()) || in_array($siteId, $row->getSiteId());
        });

        usort($data, fn (Staticroute $a, Staticroute $b) => $b->getSiteId() <=> $a->getSiteId());

        if (count($data) && $data[0]->getId()) {
            $this->assignVariablesToModel($data[0]->getObjectVars());
        } else {
            throw new NotFoundException(sprintf(
                'Static route config with name "%s" does not exist.',
                $this->model->getName()
            ));
        }
    }

    #[Override]
    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'opendxp_static_routes' => [
                'definitions' => [
                    $id => $data,
                ],
            ],
        ];
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
        $allowedProperties = ['name', 'pattern', 'reverse', 'controller',
            'variables', 'defaults', 'siteId', 'priority', 'methods', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }

        $this->saveData($this->model->getId(), $data);
    }
}
