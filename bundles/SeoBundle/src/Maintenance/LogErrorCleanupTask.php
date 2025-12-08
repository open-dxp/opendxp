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

namespace OpenDxp\Bundle\SeoBundle\Maintenance;

use Doctrine\DBAL\Connection;
use OpenDxp\Maintenance\TaskInterface;

/**
 * @internal
 */
class LogErrorCleanupTask implements TaskInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function execute(): void
    {
        // keep the history for max. 7 days (=> exactly 144h), according to the privacy policy (EU/German Law)
        // it's allowed to store the IP for 7 days for security reasons (DoS, ...)
        $limit = time() - (6 * 86400);

        $this->db->executeStatement('DELETE FROM http_error_log WHERE `date` < :limit', [
            'limit' => $limit,
        ]);
    }
}
