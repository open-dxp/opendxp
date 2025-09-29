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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\Command;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element\Service;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:search-backend-reindex',
    description: 'Re-indexes the backend search of opendxp',
    aliases: ['search-backend-reindex']
)]
class SearchBackendReindexCommand extends AbstractCommand
{
    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // clear all data
        $db = \OpenDxp\Db::get();
        $db->executeQuery('TRUNCATE `search_backend_data`;');

        $elementsPerLoop = 100;
        $types = ['asset', 'document', 'object'];

        foreach ($types as $type) {
            $baseClass = Service::getBaseClassNameForElement($type);
            $listClassName = '\\OpenDxp\\Model\\' . $baseClass . '\\Listing';
            $list = new $listClassName();
            if (method_exists($list, 'setUnpublished')) {
                $list->setUnpublished(true);
            }

            if (method_exists($list, 'setObjectTypes')) {
                $list->setObjectTypes([
                    DataObject\AbstractObject::OBJECT_TYPE_OBJECT,
                    DataObject\AbstractObject::OBJECT_TYPE_FOLDER,
                    DataObject\AbstractObject::OBJECT_TYPE_VARIANT,
                ]);
            }

            $elementsTotal = $list->getTotalCount();

            for ($i = 0; $i < (ceil($elementsTotal / $elementsPerLoop)); $i++) {
                $list->setLimit($elementsPerLoop);
                $list->setOffset($i * $elementsPerLoop);

                $this->output->writeln(
                    'Processing ' .$type . ': ' . ($list->getOffset() + $elementsPerLoop) . '/' . $elementsTotal
                );

                $elements = $list->load();
                foreach ($elements as $element) {
                    try {
                        $searchEntry = Search\Backend\Data::getForElement($element);
                        if ($searchEntry->getId() instanceof Search\Backend\Data\Id) {
                            $searchEntry->setDataFromElement($element);
                        } else {
                            $searchEntry = new Search\Backend\Data($element);
                        }

                        $searchEntry->save();
                    } catch (Exception $e) {
                        Logger::err((string) $e);
                    }
                }
                OpenDxp::collectGarbage();
                OpenDxp::deleteTemporaryFiles();
            }
        }

        $db->executeQuery('OPTIMIZE TABLE search_backend_data;');

        return 0;
    }
}
