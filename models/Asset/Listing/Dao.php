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

namespace OpenDxp\Model\Asset\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use OpenDxp\Model;
use OpenDxp\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Asset\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * Get the assets from database
     */
    public function load(): array
    {
        $assets = [];

        $queryBuilder = $this->getQueryBuilder('assets.id', 'assets.type');
        $assetsData = $this->db->fetchAllAssociative($queryBuilder->getSQL(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        $ids = [];
        foreach ($assetsData as $assetData) {
            if ($assetData['type']) {
                $ids[] = (int) $assetData['id'];
            }
        }

        // pre-warm the RuntimeCache with a single batched persistent-cache read
        Model\Element\Service::prefetchElementsByIds('asset', $ids);

        try {
            foreach ($ids as $id) {
                if (!$asset = Model\Asset::getById($id)) {
                    continue;
                }
                $assets[] = $asset;
            }
        } finally {
            // drop this batch's prefetched entries the loop did not consume
            // (e.g. when a POST_LOAD listener throws), they would otherwise
            // serve stale data to later reads in long-running processes
            Model\Element\Service::invalidatePrefetchedElementsByIds('asset', $ids);
        }

        $this->model->setAssets($assets);

        return $assets;
    }

    /**
     * @param string|string[]|null $columns
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from('assets');

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of document IDs for the specified parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList(): array
    {
        $queryBuilder = $this->getQueryBuilder('assets.id');
        $assetIds = $this->db->fetchFirstColumn($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return array_map(intval(...), $assetIds);
    }

    public function getCount(): int
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getAssets());
        }
        $idList = $this->loadIdList();

        return count($idList);
    }

    public function getTotalCount(): int
    {
        $queryBuilder = $this->getQueryBuilder();

        return $this->getTotalCountFromQueryBuilder($queryBuilder, 'assets.id');
    }
}
