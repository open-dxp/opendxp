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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\Task\Maintenance;

use Doctrine\DBAL\Exception;
use OpenDxp\Db;
use OpenDxp\Maintenance\TaskInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 */
class FullTextIndexOptimizeTask implements TaskInterface
{
    private readonly LockInterface $lock;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lock = $lockFactory->createLock(self::class, 86400 * 7, false);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function execute(): void
    {
        if ($this->lock->acquire(false)) {
            Db::get()->fetchAllAssociative('OPTIMIZE TABLE search_backend_data');
            Db::get()->fetchAllAssociative('OPTIMIZE TABLE email_log');
        }
    }
}
