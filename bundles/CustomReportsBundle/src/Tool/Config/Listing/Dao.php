<?php

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

namespace OpenDxp\Bundle\CustomReportsBundle\Tool\Config\Listing;

use OpenDxp\Bundle\CustomReportsBundle\Tool\Config;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property \OpenDxp\Bundle\CustomReportsBundle\Tool\Config\Listing $model
 */
class Dao extends \OpenDxp\Bundle\CustomReportsBundle\Tool\Config\Dao
{
    /**
     * @return Config[]
     */
    public function loadList(): array
    {
        $configs = [];

        $idList = $this->loadIdList();
        foreach ($idList as $name) {
            $configs[] = Config::getByName($name);
        }

        return $configs;
    }

    /**
     *
     * @return Config[]
     */
    public function loadForGivenUser(Model\User $user): array
    {
        $allConfigs = $this->loadList();

        if ($user->isAdmin()) {
            return $allConfigs;
        }

        $filteredConfigs = [];
        foreach ($allConfigs as $config) {
            if ($config->getShareGlobally()) {
                $filteredConfigs[] = $config;
            } elseif ($config->getSharedUserIds() && in_array($user->getId(), $config->getSharedUserIds())) {
                $filteredConfigs[] = $config;
            } elseif ($config->getSharedRoleIds() && array_intersect($user->getRoles(), $config->getSharedRoleIds())) {
                $filteredConfigs[] = $config;
            }
        }

        return $filteredConfigs;
    }

    public function getTotalCount(): int
    {
        return count($this->loadIdList());
    }
}
