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

namespace OpenDxp\Maintenance\Tasks;

use Doctrine\DBAL\Connection;
use Exception;
use OpenDxp\Maintenance\TaskInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class DbCleanupBrokenViewsTask implements TaskInterface
{
    public function __construct(private readonly Connection $db, private readonly LoggerInterface $logger)
    {
    }

    public function execute(): void
    {
        $tables = $this->db->fetchAllAssociative('SHOW FULL TABLES');
        foreach ($tables as $table) {
            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type === 'VIEW') {
                try {
                    $createStatement = $this->db->fetchAssociative('SHOW FIELDS FROM '.$name);
                } catch (Exception $e) {
                    if (str_contains($e->getMessage(), 'references invalid table')) {
                        $this->logger->error('view '.$name.' seems to be a broken one, it will be removed');
                        $this->logger->error('error message was: '.$e->getMessage());

                        $this->db->executeQuery('DROP VIEW '.$name);
                    } else {
                        $this->logger->error((string) $e);
                    }
                }
            }
        }
    }
}
