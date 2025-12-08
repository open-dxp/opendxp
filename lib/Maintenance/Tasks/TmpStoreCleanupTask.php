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
use OpenDxp\Maintenance\TaskInterface;

/**
 * @internal
 */
class TmpStoreCleanupTask implements TaskInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function execute(): void
    {
        $this->db->executeQuery('DELETE FROM tmp_store WHERE `expiryDate` < :time', ['time' => time()]);
    }
}
