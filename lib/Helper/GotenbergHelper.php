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

namespace OpenDxp\Helper;

use Exception;
use Gotenberg\Gotenberg as GotenbergAPI;
use Gotenberg\Stream;
use OpenDxp\Config;

/**
 * @internal
 */
class GotenbergHelper
{
    private static bool $validPing = false;

    /**
     *
     * @throws Exception
     */
    public static function isAvailable(): bool
    {
        if (self::$validPing) {
            return true;
        }

        if (!class_exists(GotenbergAPI::class, true)) {
            return false;
        }

        $request = null;

        $chrome = GotenbergAPI::chromium(Config::getSystemConfiguration('gotenberg')['base_url']);
        if (method_exists($chrome, 'html')) {
            // gotenberg/gotenberg-php API Client v1
            $request = $chrome->html(Stream::string('dummy.html', '<body></body>'));
        } elseif (method_exists($chrome, 'screenshot')) {
            $request = $chrome->screenshot()->html(Stream::string('dummy.html', '<body></body>'));
        }

        if ($request) {
            try {
                GotenbergAPI::send($request);
                self::$validPing = true;

                return true;
            } catch (Exception) {
                // nothing to do
            }
        }

        return false;
    }
}
