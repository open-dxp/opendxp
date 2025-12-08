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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data\Listing;

use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use OpenDxp\Logger;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Listing\Dao\AbstractDao;
use Override;

/**
 * @internal
 *
 * @property Data\Listing $model
 */
class Dao extends AbstractDao
{
    /**
     * Loads a list of entries for the specicifies parameters, returns an array of Search\Backend\Data
     *
     */
    public function load(): array
    {
        $entries = [];
        $data = $this->db->fetchAllAssociative('SELECT * FROM search_backend_data' .  $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($data as $entryData) {
            if (!in_array($entryData['maintype'], ['document', 'asset', 'object'], true)) {
                Logger::err('unknown maintype');
            }

            $element = Service::getElementById($entryData['maintype'], (int) $entryData['id']);

            if ($element) {
                $entry = new Search\Backend\Data();
                $entry->setId(new Search\Backend\Data\Id($element));
                $entry->setKey($entryData['key']);
                $entry->setIndex((int)$entryData['index']);
                $entry->setFullPath($entryData['fullpath']);
                $entry->setType($entryData['type']);
                $entry->setSubtype($entryData['subtype']);
                $entry->setUserOwner($entryData['userOwner']);
                $entry->setUserModification($entryData['userModification']);
                $entry->setCreationDate($entryData['creationDate']);
                $entry->setModificationDate($entryData['modificationDate']);
                $entry->setPublished($entryData['published'] !== 0);
                $entries[] = $entry;
            }
        }
        $this->model->setEntries($entries);

        return $entries;
    }

    public function getTotalCount(): int
    {
        return (int)$this->db->fetchOne('SELECT COUNT(*) FROM search_backend_data' . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getCount(): int|string
    {
        if (count($this->model->getEntries()) > 0) {
            return count($this->model->getEntries());
        }

        return $this->db->fetchOne('SELECT COUNT(*) as amount FROM search_backend_data '  . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    #[Override]
    protected function getCondition(): string
    {
        if ($cond = $this->model->getCondition()) {
            return ' WHERE ' . $cond . ' ';
        }

        return '';
    }
}
