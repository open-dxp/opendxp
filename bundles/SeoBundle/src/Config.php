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

namespace OpenDxp\Bundle\SeoBundle;

use Exception;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Tool\SettingsStore;

final class Config
{
    /**
     * @return array<string, string>
     *
     * @internal
     */
    public static function getRobotsConfig(): array
    {
        $config = [];
        if (RuntimeCache::isRegistered('opendxp_bundle_seo_config_robots')) {
            $config = RuntimeCache::get('opendxp_bundle_seo_config_robots');
        } else {
            try {
                $settingsStoreScope = 'robots.txt';
                $robotsSettingsIds = SettingsStore::getIdsByScope($settingsStoreScope);
                foreach ($robotsSettingsIds as $id) {
                    $robots = SettingsStore::get($id, $settingsStoreScope);
                    $siteId = preg_replace('/^robots\.txt\-/', '', $robots->getId());
                    $config[$siteId] = $robots->getData();
                }
            } catch (Exception) {
            }

            self::setRobotsConfig($config);
        }

        return $config;
    }

    /**
     * @param array<string, string> $config
     *
     * @internal
     */
    public static function setRobotsConfig(array $config): void
    {
        RuntimeCache::set('opendxp_bundle_seo_config_robots', $config);
    }
}
