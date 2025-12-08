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

use OpenDxp\Maintenance\TaskInterface;
use OpenDxp\Maintenance\Tasks\DataObject\ConcreteTaskHelperInterface;

/**
 * @internal
 */
class CleanupBrickTablesTask implements TaskInterface
{
    public function __construct(private readonly ConcreteTaskHelperInterface $helper)
    {
    }

    public function execute(): void
    {
        $this->helper->cleanupCollectionTable();
    }
}
