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

namespace OpenDxp\Image;

use Exception;
use Gotenberg\Gotenberg as GotenbergAPI;
use OpenDxp\Config;
use OpenDxp\Helper\GotenbergHelper;

/**
 * @internal
 */
class HtmlToImage
{
    private static ?string $supportedAdapter = null;

    public static function isSupported(): bool
    {
        return (bool) self::getSupportedAdapter();
    }

    private static function getSupportedAdapter(): string
    {
        if (self::$supportedAdapter !== null) {
            return self::$supportedAdapter;
        }

        self::$supportedAdapter = '';

        if (GotenbergHelper::isAvailable()) {
            /** @var GotenbergAPI|object $chrome */
            $chrome = GotenbergAPI::chromium(Config::getSystemConfiguration('gotenberg')['base_url']);
            if (method_exists($chrome, 'screenshot')) {
                // only v2 of Gotenberg lib is supported
                self::$supportedAdapter = 'gotenberg';
            }
        }

        return self::$supportedAdapter;
    }

    /**
     * @throws Exception
     */
    public static function convert(string $url, string $outputFile, ?string $sessionName = null, ?string $sessionId = null, string $windowSize = '1280,1024'): bool
    {
        return match (self::getSupportedAdapter()) {
            'gotenberg' => self::convertGotenberg(...func_get_args()),
            default => false
        };
    }

    public static function convertGotenberg(string $url, string $outputFile, ?string $sessionName = null, ?string $sessionId = null, string $windowSize = '1280,1024'): bool
    {
        try {
            /** @var GotenbergAPI|object $request */
            $request = GotenbergAPI::chromium(Config::getSystemConfiguration('gotenberg')['base_url']);
            if (method_exists($request, 'screenshot')) {
                $sizes = explode(',', $windowSize);
                $urlResponse = $request->screenshot()
                    ->width((int) $sizes[0])
                    ->height((int) $sizes[1])
                    ->png()
                    ->url($url);

                $file = GotenbergAPI::save($urlResponse, OPENDXP_SYSTEM_TEMP_DIRECTORY);

                return rename(OPENDXP_SYSTEM_TEMP_DIRECTORY . '/' . $file, $outputFile);
            }

        } catch (Exception) {
            // nothing to do
        }

        return false;
    }
}
