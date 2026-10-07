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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\DataObject\ClassDefinition\Helper;

use Error;
use OpenDxp;
use OpenDxp\Logger;

/**
 * @internal
 */
abstract class ClassResolver
{
    private static array $cache;

    protected static function resolve(
        ?string $class,
        ?callable $validationCallback = null,
        bool $showError = true
    ): ?object {
        if (!$class) {
            return null;
        }

        $return = null;
        if ($showError) {
            $return = self::resolveClass($class, $validationCallback);
        }

        try {
            $return = self::resolveClass($class, $validationCallback);
        } catch (Error $e) {
            Logger::error($e->getMessage());
        }

        return $return;
    }

    /**
     * The container shares its services itself. Kept here, a service would outlive a container that was built again,
     * as it is for every test.
     */
    private static function resolveClass(string $class, ?callable $validationCallback): ?object
    {
        if (str_starts_with($class, '@')) {
            return self::returnValidServiceOrNull(OpenDxp::getContainer()->get(substr($class, 1)), $validationCallback);
        }

        return self::$cache[$class] ??= self::returnValidServiceOrNull(new $class, $validationCallback);
    }

    private static function returnValidServiceOrNull(object $service, ?callable $validationCallback = null): ?object
    {
        if ($validationCallback && !$validationCallback($service)) {
            return null;
        }

        return $service;
    }
}
