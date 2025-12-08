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

namespace OpenDxp\Model\Asset\Video\Thumbnail\Config;

use Exception;
use OpenDxp;
use OpenDxp\Messenger\CleanupThumbnailsMessage;
use OpenDxp\Model;
use Override;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Asset\Video\Thumbnail\Config $model
 */
class Dao extends Model\Dao\OpenDxpLocationAwareConfigDao
{
    private const string CONFIG_KEY = 'video_thumbnails';

    #[Override]
    public function configure(): void
    {
        $config = \OpenDxp\Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['assets']['video']['thumbnails']['definitions'],
            'settingsStoreScope' => 'opendxp_video_thumbnails',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     *
     * @throws Exception
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
                'Thumbnail with ID "%s" does not exist.',
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
        $allowedProperties = ['name', 'description', 'group', 'items', 'medias',
            'videoBitrate', 'audioBitrate', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }

        $this->saveData($this->model->getName(), $data);
        $this->autoClearTempFiles();
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getName());
        $this->autoClearTempFiles();
    }

    protected function autoClearTempFiles(): void
    {
        $enabled = \OpenDxp\Config::getSystemConfiguration('assets')['video']['thumbnails']['auto_clear_temp_files'];
        if ($enabled) {
            OpenDxp::getContainer()->get('messenger.bus.opendxp-core')->dispatch(
                new CleanupThumbnailsMessage('video', $this->model->getName())
            );
        }
    }

    #[Override]
    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'opendxp' => [
                'assets' => [
                    'video' => [
                        'thumbnails' => [
                            'definitions' => [
                                $id => $data,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
