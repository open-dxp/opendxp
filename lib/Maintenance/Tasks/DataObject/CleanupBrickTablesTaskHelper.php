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

namespace OpenDxp\Maintenance\Tasks\DataObject;

use Doctrine\DBAL\Connection;
use OpenDxp\Model\DataObject\Objectbrick\Definition;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class CleanupBrickTablesTaskHelper implements ConcreteTaskHelperInterface
{
    private const string OPENDXP_OBJECTBRICK_CLASS_DIRECTORY = OPENDXP_CLASS_DEFINITION_DIRECTORY . '/objectbricks';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DataObjectTaskHelperInterface $helper,
        private readonly Connection $db
    ) {
    }

    public function cleanupCollectionTable(): void
    {
        $collectionNames =
            $this->helper->getCollectionNames(self::OPENDXP_OBJECTBRICK_CLASS_DIRECTORY);

        if ($collectionNames === []) {
            return;
        }

        $tableTypes = ['store', 'query', 'localized'];
        foreach ($tableTypes as $tableType) {
            $prefix = 'object_brick_' . $tableType . '_';
            $tableNames = $this->db->fetchAllAssociative("SHOW TABLES LIKE '" . $prefix . "%'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);

                if (str_starts_with($tableName, 'object_brick_localized_query_')) {
                    continue;
                }

                $fieldDescriptor = substr($tableName, strlen($prefix));
                $idx = strpos($fieldDescriptor, '_');
                $brickType = substr($fieldDescriptor, 0, $idx);
                $brickType = $collectionNames[strtolower($brickType)] ?? $brickType;

                if (!$this->checkIfBrickExists($brickType, $tableName)) {
                    continue;
                }

                $classId = substr($fieldDescriptor, $idx + 1);
                $this->helper->cleanupTable($tableName, $classId);
            }
        }
    }

    private function checkIfBrickExists(string $brickType, string $tableName): bool
    {
        $brickDef = Definition::getByKey($brickType);
        if (!$brickDef) {
            $this->logger->error("Brick '" . $brickType . "' not found. Please check table " . $tableName);

            return false;
        }

        return true;
    }
}
