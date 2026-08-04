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

namespace OpenDxp\Model\Document\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use OpenDxp\Model;
use OpenDxp\Model\Document;
use OpenDxp\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Document\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * Loads a list of objects (all are an instance of Document) for the given parameters an return them
     *
     * @return Document[]
     */
    public function load(): array
    {
        $documents = [];
        $select = $this->getQueryBuilder('documents.id', 'documents.type');

        $documentsData = $this->db->fetchAllAssociative($select->getSQL(), $select->getParameters(), $select->getParameterTypes());

        $ids = [];
        foreach ($documentsData as $documentData) {
            if ($documentData['type']) {
                $ids[] = (int) $documentData['id'];
            }
        }

        // pre-warm the RuntimeCache with a single batched persistent-cache read
        Model\Element\Service::prefetchElementsByIds('document', $ids);

        try {
            foreach ($ids as $id) {
                if (!$doc = Document::getById($id)) {
                    continue;
                }
                $documents[] = $doc;
            }
        } finally {
            // drop this batch's prefetched entries the loop did not consume
            // (e.g. when a POST_LOAD listener throws), they would otherwise
            // serve stale data to later reads in long-running processes
            Model\Element\Service::invalidatePrefetchedElementsByIds('document', $ids);
        }

        $this->model->setDocuments($documents);

        return $documents;
    }

    /**
     * @param string|string[]|null $columns
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from('documents');

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList(): array
    {
        $queryBuilder = $this->getQueryBuilder('documents.id');
        $documentIds = $this->db->fetchFirstColumn($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return array_map(intval(...), $documentIds);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function loadIdPathList(): array
    {
        $queryBuilder = $this->getQueryBuilder('documents.id', 'CONCAT(documents.path, documents.key) as `path`');

        return $this->db->fetchAllAssociative($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
    }

    public function getCount(): int
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getDocuments());
        }
        $idList = $this->loadIdList();

        return count($idList);
    }

    public function getTotalCount(): int
    {
        $queryBuilder = $this->getQueryBuilder();

        return $this->getTotalCountFromQueryBuilder($queryBuilder, 'documents.id');
    }
}
