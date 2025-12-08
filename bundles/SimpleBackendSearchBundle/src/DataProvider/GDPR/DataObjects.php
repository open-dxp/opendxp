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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\DataProvider\GDPR;

use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Service\GridData;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element;
use Override;

class DataObjects extends DataProvider\DataObjects
{
    #[Override]
    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, ?string $sort = null): array
    {
        if (empty($id) && empty($firstname) && empty($lastname) && empty($email)) {
            return ['data' => [], 'success' => true, 'total' => 0];
        }

        $offset = $start;
        $offset = $offset ?: 0;
        $limit = $limit ?: 50;

        $searcherList = new Data\Listing();
        $conditionParts = [];
        $db = \OpenDxp\Db::get();

        //id search
        if ($id) {
            $conditionParts[] = '( MATCH (`data`,`properties`) AGAINST (+"' . $id . '" IN BOOLEAN MODE) )';
        }

        // search for firstname, lastname, email
        if ($firstname || $lastname || $email) {
            $firstname = $this->prepareQueryString($firstname);
            $lastname = $this->prepareQueryString($lastname);
            $email = $this->prepareQueryString($email);

            $queryString = ($firstname ? '+"' . $firstname . '"' : '') . ' ' . ($lastname ? '+"' . $lastname . '"' : '') . ' ' . ($email ? '+"' . $email . '"' : '');
            $conditionParts[] = '( MATCH (`data`,`properties`) AGAINST ("' . $db->quote($queryString) . '" IN BOOLEAN MODE) )';
        }

        $conditionParts[] = '( maintype = "object" AND `type` IN ("object", "variant") )';

        $classnames = [];
        if ($this->config['classes']) {
            foreach ($this->config['classes'] as $classname => $classConfig) {
                if ($classConfig['include'] == true) {
                    $classnames[] = $classname;
                }
            }
        }

        if ($classnames) {
            $conditionClassnameParts = [];
            foreach ($classnames as $classname) {
                $conditionClassnameParts[] = $db->quote($classname);
            }
            $conditionParts[] = '( subtype IN (' . implode(',', $conditionClassnameParts) . ') )';
        }

        $condition = implode(' AND ', $conditionParts);
        $searcherList->setCondition($condition);

        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        $sortingSettings = QueryParams::extractSortingSettings(['sort' => $sort]);
        if ($sortingSettings['orderKey']) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                'classname' => 'subtype',
            ];

            $sort = $sortingSettings['orderKey'];
            if (array_key_exists($sortingSettings['orderKey'], $sortMapping)) {
                $sort = $sortMapping[$sortingSettings['orderKey']];
            }
            $searcherList->setOrderKey($sort);
        }
        if ($sortingSettings['order']) {
            $searcherList->setOrder($sortingSettings['order']);
        }

        $hits = $searcherList->load();

        $elements = [];
        foreach ($hits as $hit) {
            $element = Element\Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element instanceof Concrete) {
                $data = GridData\DataObject::getData($element);
                $data['__gdprIsDeletable'] = $this->config['classes'][$element->getClassName()]['allowDelete'] ?? false;
                $elements[] = $data;
            }
        }

        $totalMatches = $searcherList->getTotalCount();

        return ['data' => $elements, 'success' => true, 'total' => $totalMatches];
    }
}
