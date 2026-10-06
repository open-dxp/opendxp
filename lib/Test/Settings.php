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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Test;

use OpenDxp\Config;

final class Settings
{
    /**
     * The override holds until the test ends. The test case of open-dxp/test-foundation resets the system
     * configuration after every test.
     *
     * @param array<string, mixed> $settings
     */
    public static function override(array $settings): void
    {
        Config::setSystemConfiguration(
            array_replace_recursive(
                Config::getSystemConfiguration(),
                $settings,
            ),
        );
    }
}
