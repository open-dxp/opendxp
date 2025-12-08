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

namespace OpenDxp\Event\Model\DataObject\ClassificationStore;

use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Contracts\EventDispatcher\Event;

class StoreConfigEvent extends Event
{
    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(protected StoreConfig $storeConfig)
    {
    }

    public function getStoreConfig(): StoreConfig
    {
        return $this->storeConfig;
    }

    public function setStoreConfig(StoreConfig $storeConfig): void
    {
        $this->storeConfig = $storeConfig;
    }
}
