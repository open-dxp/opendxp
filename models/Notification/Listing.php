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

namespace OpenDxp\Model\Notification;

use OpenDxp\Model;
use OpenDxp\Model\Listing\AbstractListing;
use Override;

/**
 * @method Listing\Dao getDao()
 * @method Model\Notification[] load()
 */
class Listing extends AbstractListing
{
    #[Override]
    public function isValidOrderKey(string $key): bool
    {
        return true;
    }

    /**
     *
     * @return Model\Notification[]
     */
    public function getItems(int $offset, ?int $limit): array
    {
        $this->setOffset($offset);
        $this->setLimit($limit);

        return $this->getData();
    }

    /**
     * @return Model\Notification[]
     */
    public function getNotifications(): array
    {
        return $this->getData();
    }

    /**
     * @param Model\Notification[]|null $notifications
     *
     * @return $this
     */
    public function setNotifications(?array $notifications): static
    {
        return $this->setData($notifications);
    }
}
