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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;

use Exception;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend;
use OpenDxp\Db\Helper;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\Dao\AbstractDao;
use OpenDxp\Model\Element\ElementType;

/**
 * @internal
 *
 * @property Backend\Data $model
 */
class Dao extends AbstractDao
{
    public function getForElement(Model\Element\ElementInterface $element): void
    {
        try {
            if ($element instanceof Model\Document) {
                $maintype = ElementType::Document->value;
            } elseif ($element instanceof Model\Asset) {
                $maintype = ElementType::Asset->value;
            } elseif ($element instanceof Model\DataObject\AbstractObject) {
                $maintype = ElementType::Object->value;
            } else {
                throw new Exception('unknown type of element with id [ '.$element->getId().' ] ');
            }

            $data = $this->db->fetchAssociative('SELECT * FROM search_backend_data WHERE id = ? AND maintype = ? ', [$element->getId(), $maintype]);
            if (is_array($data)) {
                unset($data['id']);
                $data['published'] = (bool) $data['published'];
                $this->assignVariablesToModel($data);
                $this->model->setId(new Backend\Data\Id($element));
            }
        } catch (Exception) {
        }
    }

    public function save(): void
    {
        $oldFullPath = $this->db->fetchOne('SELECT fullpath FROM search_backend_data WHERE id = :id AND maintype = :type FOR UPDATE', [
            'id' => $this->model->getId()->getId(),
            'type' => $this->model->getId()->getType(),
        ]);

        if ($oldFullPath && $oldFullPath !== $this->model->getFullPath()) {
            $this->db->executeStatement(
                'UPDATE search_backend_data
                    SET fullpath = REPLACE(fullpath, ?, ?)
                    WHERE fullpath LIKE ? AND maintype = ?',
                [
                    $oldFullPath . '/',
                    $this->model->getFullPath() . '/',
                    Helper::escapeLike($oldFullPath) . '/%',
                    $this->model->getId()->getType(),
                ]
            );
        }

        $data = [
            'id' => $this->model->getId()->getId(),
            'key' => $this->model->getKey(),
            'index' => $this->model->getIndex(),
            'fullpath' => $this->model->getFullPath(),
            'maintype' => $this->model->getId()->getType(),
            'type' => $this->model->getType(),
            'subtype' => $this->model->getSubtype(),
            'published' => $this->model->isPublished(),
            'creationDate' => $this->model->getCreationDate(),
            'modificationDate' => $this->model->getmodificationDate(),
            'userOwner' => $this->model->getUserOwner(),
            'userModification' => $this->model->getUserModification(),
            'data' => $this->model->getData(),
            'properties' => $this->model->getProperties(),
        ];

        Helper::upsert($this->db, 'search_backend_data', $data, $this->getPrimaryKey('search_backend_data'));
    }

    /**
     * Deletes from database
     */
    public function delete(): void
    {
        if ($this->model->getId() instanceof Backend\Data\Id) {
            $this->db->delete('search_backend_data', [
                'id' => $this->model->getId()->getId(),
                'maintype' => $this->model->getId()->getType(),
            ]);
        } else {
            Logger::alert('Cannot delete Search\\Backend\\Data, ID is empty');
        }
    }

    public function getMinWordLengthForFulltextIndex(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT @@innodb_ft_min_token_size');
        } catch (Exception) {
            return 3;
        }
    }

    public function getMaxWordLengthForFulltextIndex(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT @@innodb_ft_max_token_size');
        } catch (Exception) {
            return 84;
        }
    }
}
