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

namespace OpenDxp\Security;

/**
 * @internal
 */
class SecurityHelper
{
    public static function convertHtmlSpecialChars(?string $text): ?string
    {
        if (is_string($text)) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
        }

        return null;
    }

    public static function convertHtmlSpecialCharsArrayKeys(array &$array, array $keys): void
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $array[$key] = self::convertHtmlSpecialChars($array[$key]);
            }
        }
    }

    /**
     * A browser runs a javascript: or vbscript: URL as a script. It also runs a data: URL that is no plain image.
     */
    public static function isScriptUrl(string $url): bool
    {
        // A browser decodes character references and skips whitespace and control characters in a URL.
        // So "java&#9;script:" runs as well.
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $url = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $url));

        if (str_starts_with($url, 'javascript:') || str_starts_with($url, 'vbscript:')) {
            return true;
        }

        return str_starts_with($url, 'data:') && preg_match('#^data:image/(?!svg)#', $url) !== 1;
    }

    /**
     * Rebuilds a string of HTML attributes, such as `data-id="1" download`.
     * An event handler, such as onclick, is dropped. Every value is escaped.
     */
    public static function filterHtmlAttributes(string $attributes): string
    {
        preg_match_all(
            '/([^\s"\'<>\/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'<>=`]+)))?/',
            $attributes,
            $matches,
            PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
        );

        $filtered = [];
        foreach ($matches as $match) {
            $name = $match[1];
            if (str_starts_with(strtolower($name), 'on')) {
                continue;
            }

            $value = $match[2] ?? $match[3] ?? $match[4];
            $filtered[] = $value === null ? $name : sprintf('%s="%s"', $name, self::convertHtmlSpecialChars($value));
        }

        return implode(' ', $filtered);
    }
}
