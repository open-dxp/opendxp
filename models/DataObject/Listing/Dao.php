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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\DataObject\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Exception;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \OpenDxp\Model\DataObject\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    public function getTableName(): string
    {
        return 'objects';
    }

    /**
     * @param string|string[]|null $columns
     *
     * @throws Exception
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from($this->getTableName());

        // apply joins
        $this->applyJoins($queryBuilder);

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of objects for the specicifies parameters, returns an array of DataObject\AbstractObject elements
     */
    public function load(): array
    {
        // load id's
        $list = $this->loadIdList();

        // pre-warm the RuntimeCache with a single batched persistent-cache read
        Model\Element\Service::prefetchElementsByIds('object', $list);

        $objects = [];

        try {
            foreach ($list as $id) {
                if ($object = DataObject::getById($id)) {
                    $objects[] = $object;
                }
            }
        } finally {
            // drop this batch's prefetched entries the loop did not consume
            // (e.g. when a POST_LOAD listener throws), they would otherwise
            // serve stale data to later reads in long-running processes
            Model\Element\Service::invalidatePrefetchedElementsByIds('object', $list);
        }

        $this->model->setObjects($objects);

        return $objects;
    }

    public function getTotalCount(): int
    {
        $queryBuilder = $this->getQueryBuilder();

        return $this->getTotalCountFromQueryBuilder($queryBuilder, $this->getTableName() . '.id');
    }

    public function getCount(): int
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getObjects());
        }
        $idList = $this->loadIdList();

        return count($idList);
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList(): array
    {
        $queryBuilder = $this->getQueryBuilder(sprintf('%s as id', $this->getTableName() . '.id'), sprintf('%s as `type`', $this->getTableName() . '.type'));
        $objectIds = $this->db->fetchFirstColumn($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return array_map(intval(...), $objectIds);
    }

    protected function applyJoins(DoctrineQueryBuilder $queryBuilder): static
    {
        return $this;
    }
}
