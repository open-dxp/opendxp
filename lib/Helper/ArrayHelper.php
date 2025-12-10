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

class ArrayHelper
{
    public static function inArrayCaseInsensitive(string $needle, array $haystack): bool
    {
        return in_array(strtolower($needle), array_map(strtolower(...), $haystack), false);
    }

    /**
     * @return false|int|string the key for needle if it is found in the array, false otherwise.
     */
    public static function arraySearchCaseInsensitive(string $needle, array $haystack): false|int|string
    {
        return array_search(strtolower($needle), array_map(strtolower(...), $haystack), false);
    }

    /**
     * Wrapper for explode() to get a trimmed array
     *
     * @return string[]
     *
     * @phpstan-param non-empty-string $delimiter
     */
    public static function explodeAndTrim(string $delimiter, string $string, int $limit = PHP_INT_MAX, bool $useArrayFilter = true): array
    {
        $exploded = explode($delimiter, $string, $limit);
        foreach ($exploded as $key => $value) {
            $exploded[$key] = trim($value);
        }

        return $useArrayFilter ? array_filter($exploded) : $exploded;
    }

    /**
     * @return array<string, mixed>
     */
    public static function objectToArray(object $node): array
    {
        // dirty hack, should be replaced
        $paj = json_encode($node);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        return @json_decode($paj, true);
    }

    public static function arrayToQueryString(array $args): string
    {
        return urldecode(http_build_query($args));
    }
}
