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

namespace OpenDxp\Tool;

use DateTimeInterface;
use DateTimeZone;
use OpenDxp\Logger;

/**
 * @internal
 */
final class UserTimezone
{
    private static ?string $userTimezone = null;

    public static function setUserTimezone(?string $userTimezone): void
    {
        if ($userTimezone !== null && !in_array($userTimezone, timezone_identifiers_list(DateTimeZone::ALL_WITH_BC))) {
            Logger::error('Invalid user timezone: ' . $userTimezone);
            $userTimezone = null;
        }
        self::$userTimezone = $userTimezone;
    }

    public static function getUserTimezone(): ?string
    {
        return self::$userTimezone;
    }

    public static function applyTimezone(DateTimeInterface $date): DateTimeInterface
    {
        if (self::getUserTimezone() && method_exists($date, 'setTimezone')) {
            return $date->setTimezone(new DateTimeZone(self::getUserTimezone()));
        }

        return $date;
    }
}
