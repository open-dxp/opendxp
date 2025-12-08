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

namespace OpenDxp\Model\Property;

use OpenDxp\Db\Helper;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Property $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Save object to database
     */
    public function save(): void
    {
        $data = $this->model->getData();

        if (in_array($this->model->getType(), ['object', 'asset', 'document'])) {
            $data = $data instanceof Model\Element\ElementInterface ? $data->getId() : null;
        }

        if (is_array($data) || is_object($data)) {
            $data = \OpenDxp\Tool\Serialize::serialize($data);
        }

        $cpath = $this->model->getCpath();
        if (empty($cpath)) {
            $element = Model\Element\Service::getElementById($this->model->getCtype(), $this->model->getCid());
            if ($element instanceof Model\Element\ElementInterface) {
                $cpath = $element->getRealFullPath();
            }
        }

        $saveData = [
            'cid' => $this->model->getCid(),
            'ctype' => $this->model->getCtype(),
            'cpath' => $this->model->getCpath(),
            'name' => $this->model->getName(),
            'type' => $this->model->getType(),
            'inheritable' => (int)$this->model->getInheritable(),
            'data' => $data,
        ];

        Helper::upsert($this->db, 'properties', $saveData, $this->getPrimaryKey('properties'));
    }
}
