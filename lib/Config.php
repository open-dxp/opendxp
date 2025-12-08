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

namespace OpenDxp;

use ArrayAccess;
use Exception;
use OpenDxp;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Config\ReportConfigWriter;
use OpenDxp\Event\SystemEvents;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Tool\SettingsStore;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Yaml\Yaml;

final class Config implements ArrayAccess
{
    /**
     * @var array<string, string>
     */
    protected static array $configFileCache = [];

    protected static ?string $environment = null;

    /**
     * @var array<string, mixed>|null
     */
    protected static ?array $systemConfig = null;

    public function offsetExists($offset): bool
    {
        return self::getSystemConfiguration($offset) !== null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new Exception("modifying the config isn't allowed");
    }

    public function offsetUnset($offset): void
    {
        throw new Exception("modifying the config isn't allowed");
    }

    /**
     *
     *
     * @return array<string, mixed>|null
     */
    public function offsetGet($offset): ?array
    {
        return self::getSystemConfiguration($offset);
    }

    /**
     * @internal
     *
     * @param string $name - name of configuration file. slash is allowed for subdirectories.
     *
     */
    public static function locateConfigFile(string $name): string
    {
        if (!isset(self::$configFileCache[$name])) {
            $pathsToCheck = [
                OPENDXP_CUSTOM_CONFIGURATION_DIRECTORY,
                OPENDXP_CONFIGURATION_DIRECTORY,
            ];
            $file = null;

            // check for environment configuration
            $env = self::getEnvironment();
            if ($env) {
                $fileExt = pathinfo($name, PATHINFO_EXTENSION);
                $pureName = str_replace('.' . $fileExt, '', $name);
                foreach ($pathsToCheck as $path) {
                    $tmpFile = $path . '/' . $pureName . '_' . $env . '.' . $fileExt;
                    if (file_exists($tmpFile)) {
                        $file = $tmpFile;

                        break;
                    }
                }
            }

            //check for config file without environment configuration
            if (!$file) {
                foreach ($pathsToCheck as $path) {
                    $tmpFile = $path . '/' . $name;
                    if (file_exists($tmpFile)) {
                        $file = $tmpFile;

                        break;
                    }
                }
            }

            //get default path in opendxp configuration directory
            if (!$file) {
                $file = OPENDXP_CONFIGURATION_DIRECTORY . '/' . $name;
            }

            self::$configFileCache[$name] = $file;
        }

        return self::$configFileCache[$name];
    }

    /**
     * @param array<string, mixed>|null $configuration
     *
     * @internal ONLY FOR TESTING PURPOSES IF NEEDED FOR SPECIFIC TEST CASES
     */
    public static function setSystemConfiguration(?array $configuration, ?string $offset = null): void
    {
        if (null !== $offset) {
            self::getSystemConfiguration();
            self::$systemConfig[$offset] = $configuration;
        } else {
            self::$systemConfig = $configuration;
        }
    }

    /**
     * @return null|array<string, mixed>
     *
     * @internal
     */
    public static function getSystemConfiguration(?string $offset = null): ?array
    {
        if (null === self::$systemConfig && $container = OpenDxp::getContainer()) {

            $settings = $container->getParameter('opendxp.config');

            $saveSettingsEvent = new GenericEvent(null, [
                'settings' => $settings,
            ]);
            $eventDispatcher = $container->get('event_dispatcher');
            $eventDispatcher->dispatch($saveSettingsEvent, SystemEvents::GET_SYSTEM_CONFIGURATION);
            $settings = $saveSettingsEvent->getArgument('settings');

            self::$systemConfig = $settings;
        }

        if (null !== $offset) {
            return self::$systemConfig[$offset] ?? null;
        }

        return self::$systemConfig;
    }

    /**
     *
     *
     * @internal
     */
    public static function getWebsiteConfigRuntimeCacheKey(?string $languange = null): string
    {
        $cacheKey = 'opendxp_config_website';
        if ($languange) {
            $cacheKey .= '_' . $languange;
        }

        return $cacheKey;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getWebsiteConfig(?string $language = null): array
    {
        if (RuntimeCache::isRegistered(self::getWebsiteConfigRuntimeCacheKey($language))) {
            $config = RuntimeCache::get(self::getWebsiteConfigRuntimeCacheKey($language));
        } else {
            $cacheKey = 'website_config';
            if ($language) {
                $cacheKey .= '_' . $language;
            }

            $siteId = 0;
            if (Model\Site::isSiteRequest()) {
                $siteId = Model\Site::getCurrentSite()->getId();
            } elseif (Tool::isFrontendRequestByAdmin()) {
                // this is necessary to set the correct settings in editmode/preview (using the main domain)
                // we cannot use the document resolver service here, because we need the document on the main request
                $originDocument = OpenDxp::getContainer()->get('request_stack')->getMainRequest()->get(DynamicRouter::CONTENT_KEY);
                if ($originDocument) {
                    $site = Tool\Frontend::getSiteForDocument($originDocument);
                    if ($site) {
                        $siteId = $site->getId();
                    }
                }
            }

            if ($siteId) {
                $cacheKey = $cacheKey . '_site_' . $siteId;
            }

            $config = Cache::load($cacheKey);
            if (!$config) {
                $config = [];
                $cacheTags = ['website_config', 'system', 'config', 'output'];

                $list = new Model\WebsiteSetting\Listing();
                $list = $list->load();

                foreach ($list as $item) {
                    $itemSiteId = $item->getSiteId();

                    if ($itemSiteId && $itemSiteId !== $siteId) {
                        continue;
                    }

                    $itemLanguage = $item->getLanguage();

                    if ($itemLanguage && $language !== $itemLanguage) {
                        continue;
                    }

                    $key = $item->getName();

                    if (!$itemLanguage && isset($config[$key])) {
                        continue;
                    }

                    $s = match ($item->getType()) {
                        'document', 'asset', 'object' => $item->getData(),
                        'bool' => (bool) $item->getData(),
                        'text' => (string) $item->getData(),
                        default => null,
                    };

                    if ($s instanceof Model\Element\ElementInterface) {
                        $elementCacheKey = $s->getCacheTag();
                        $cacheTags[$elementCacheKey] = $elementCacheKey;
                    }

                    if (isset($s)) {
                        $config[$key] = $s;
                    }
                }

                //TODO resolve for all langs, current lang first, then no lang
                Cache::save($config, $cacheKey, $cacheTags, null, 998);
            } elseif (is_array($config)) {
                foreach ($config as $setting) {
                    if ($setting instanceof ElementInterface) {
                        $elementCacheKey = $setting->getCacheTag();
                        if (!RuntimeCache::isRegistered($elementCacheKey)) {
                            RuntimeCache::set($elementCacheKey, $setting);
                        }
                    }
                }
            }

            self::setWebsiteConfig($config, $language);
        }

        return $config;
    }

    /**
     * @param array<string, mixed>|null $config
     *
     * @internal
     */
    public static function setWebsiteConfig(?array $config, ?string $language = null): void
    {
        RuntimeCache::set(self::getWebsiteConfigRuntimeCacheKey($language), $config);
    }

    /**
     * Returns whole website config or only a given setting for the current site
     *
     * @param string|null $key  Config key to directly load. If null, the whole config will be returned
     * @param mixed $default    Default value to use if the key is not set
     *
     */
    public static function getWebsiteConfigValue(?string $key = null, mixed $default = null, ?string $language = null): mixed
    {
        $config = self::getWebsiteConfig($language);
        if (null !== $key) {
            return $config[$key] ?? $default;
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     *
     * @internal
     */
    public static function getReportConfig(): array
    {
        $config = [];
        if (RuntimeCache::isRegistered('opendxp_config_report')) {
            $config = RuntimeCache::get('opendxp_config_report');
        } else {
            try {
                $configJson = SettingsStore::get(
                    ReportConfigWriter::REPORT_SETTING_ID, ReportConfigWriter::REPORT_SETTING_SCOPE
                );

                if ($configJson) {
                    $config = json_decode($configJson->getData(), true);
                }
            } catch (Exception) {
                // nothing to do
            }
        }

        self::setReportConfig($config);

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal
     */
    public static function setReportConfig(array $config): void
    {
        RuntimeCache::set('opendxp_config_report', $config);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal
     */
    public static function setModelClassMappingConfig(array $config): void
    {
        RuntimeCache::set('opendxp_config_model_classmapping', $config);
    }

    /**
     * @param array<string, mixed> $runtimeConfig
     *
     * @internal
     */
    public static function inPerspective(array $runtimeConfig, string $key): bool
    {
        if (!isset($runtimeConfig['toolbar']) || !$runtimeConfig['toolbar']) {
            return true;
        }
        $parts = explode('.', $key);
        $menuItems = $runtimeConfig['toolbar'];
        $counter = count($parts);

        for ($i = 0; $i < $counter; $i++) {
            $part = $parts[$i];

            if (!isset($menuItems[$part])) {
                break;
            }

            $menuItem = $menuItems[$part];

            if (is_array($menuItem)) {
                if (isset($menuItem['hidden']) && $menuItem['hidden']) {
                    return false;
                }

                if (!($menuItem['items'] ?? null)) {
                    break;
                }
                $menuItems = $menuItem['items'];
            } else {
                return $menuItem;
            }
        }

        return true;
    }

    public static function getEnvironment(): string
    {
        return $_SERVER['APP_ENV'] ?? 'dev';
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     *
     * @internal
     */
    public static function getConfigInstance(string $file): array
    {
        $fileType = pathinfo($file, PATHINFO_EXTENSION);
        if (file_exists($file)) {
            $content = $fileType == 'yaml' ? Yaml::parseFile($file) : include($file);

            if (is_array($content)) {
                return $content;
            }
        } else {
            throw new Exception($file . " doesn't exist");
        }

        throw new Exception($file . ' is invalid');
    }
}
