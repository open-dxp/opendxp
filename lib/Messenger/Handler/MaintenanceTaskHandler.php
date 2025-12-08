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

namespace OpenDxp\Messenger\Handler;

use OpenDxp\Maintenance\ExecutorInterface;
use OpenDxp\Messenger\MaintenanceTaskMessage;

/**
 * @internal
 */
class MaintenanceTaskHandler
{
    public function __construct(
        private readonly ExecutorInterface $maintenanceExecutor
    ) {
    }

    public function __invoke(MaintenanceTaskMessage $message): void
    {
        $this->maintenanceExecutor->executeTask($message->getName());
    }
}
