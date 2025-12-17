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

namespace OpenDxp\Helper;

use Closure;
use ReflectionFunction;
use SplFileObject;

class StringHelper
{
    public static function isValidJson(mixed $string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        return json_validate($string);
    }

    /**
     * @param string[] $values
     */
    public static function replacePcreBackreferences(string $string, array $values): string
    {
        array_unshift($values, '');
        $string = str_replace('\$', '###PCRE_PLACEHOLDER###', $string);

        foreach ($values as $key => $value) {
            $string = str_replace('$' . $key, $value, $string);
        }

        return str_replace('###URLENCODE_PLACEHOLDER###', '$', $string);
    }

    public static function urlEncodeIgnoreSlash(string $var): string
    {
        $scheme = parse_url($var, PHP_URL_SCHEME);

        if ($scheme) {
            $var = str_replace($scheme . '://', '', $var);
        }

        $placeholder = 'x-X-x-ignore-' . md5(microtime()) . '-slash-x-X-x';

        $var = str_replace('/', $placeholder, $var);
        $var = rawurlencode($var);
        $var = str_replace($placeholder, '/', $var);

        if ($scheme) {
            $var = $scheme . '://' . $var;
        }

        return preg_replace("/%40([\d]+)x\./", '@$1x.', $var);
    }

    public static function closureHash(Closure $closure): string
    {
        $ref = new ReflectionFunction($closure);
        $file = new SplFileObject($ref->getFileName());
        $file->seek($ref->getStartLine() - 1);

        $content = '';
        while ($file->key() < $ref->getEndLine()) {
            $content .= $file->current();
            $file->next();
        }

        return md5(json_encode([
            $content,
            $ref->getStaticVariables(),
        ]));
    }

    public static function generateRandomSymfonySecret(): string
    {
        return base64_encode(random_bytes(24));
    }

    public static function implodeRecursive(array $array, string $glue): string
    {
        $ret = '';

        foreach ($array as $item) {
            if (is_array($item)) {
                $ret .= self::implodeRecursive($item, $glue) . $glue;
            } else {
                $ret .= $item . $glue;
            }
        }

        return substr($ret, 0, -strlen($glue));
    }

    /**
     * @param array $array with attribute names as keys, and values as values
     */
    public static function arrayToHtmlAttributeString(array $array): string
    {
        $data = [];

        foreach ($array as $key => $value) {
            if (is_scalar($value)) {
                $data[] = sprintf('%s="%s"', $key, htmlspecialchars((string) $value));
            } elseif (is_string($key) && is_null($value)) {
                $data[] = $key;
            }
        }

        return implode(' ', $data);
    }
}
