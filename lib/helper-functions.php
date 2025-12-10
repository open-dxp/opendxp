<?php

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

use OpenDxp\Helper;

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * @return array<string, mixed>
 */
function xmlToArray(string $file): array
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "xmlToArray()" is deprecated and will be removed in 2.0.');

    $xml = simplexml_load_file($file, null, LIBXML_NOCDATA);
    $json = json_encode((array) $xml);

    return json_decode($json, true);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function gzcompressfile(string $source, ?int $level = null, ?string $target = null): false|string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "gzcompressfile()" is deprecated, use "\OpenDxp\Helper\FileSystemHelper::gzCompressFile()" instead.');

    return Helper\FileSystemHelper::gzCompressFile($source, $level, $target);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function is_json(mixed $string): bool
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "is_json()" is deprecated, use "\OpenDxp\Helper\StringHelper::isValidJson()" instead.');

    return Helper\StringHelper::isValidJson($string);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function foldersize(string $path): int
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "foldersize()" is deprecated and will be removed in 2.0..');

    $total_size = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path, '/'). '/';

    foreach ($files as $t) {
        if ($t !== '.' && $t !== '..') {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                $size = foldersize($currentFile);
                $total_size += $size;
            } else {
                $size = filesize($currentFile);
                $total_size += $size;
            }
        }
    }

    return $total_size;
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function replace_pcre_backreferences(string $string, array $values): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "replace_pcre_backreferences()" is deprecated, use "\OpenDxp\Helper\StringHelper::replacePcreBackreferences()" instead.');

    return Helper\StringHelper::replacePcreBackreferences($string, $values);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * @param mixed[] $array
 *
 * @return mixed[]
 */
function array_htmlspecialchars(array $array): array
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "array_htmlspecialchars()" is deprecated and will be removed in 2.0.');

    foreach ($array as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
            $array[$key] = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
        } elseif (is_array($value)) {
            $array[$key] = array_htmlspecialchars($value);
        }
    }

    return $array;
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function in_arrayi(string $needle, array $haystack): bool
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "in_arrayi()" is deprecated, use "\OpenDxp\Helper\ArrayHelper::inArrayCaseInsensitive()" instead.');

    return Helper\ArrayHelper::inArrayCaseInsensitive($needle, $haystack);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * @return false|int|string the key for needle if it is found in the array, false otherwise.
 */
function array_searchi(string $needle, array $haystack): false|int|string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "array_searchi()" is deprecated, use "\OpenDxp\Helper\ArrayHelper::arraySearchCaseInsensitive()" instead.');

    return Helper\ArrayHelper::arraySearchCaseInsensitive($needle, $haystack);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * @return array<string, mixed>
 */
function object2array(object $node): array
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "object2array()" is deprecated, use "\OpenDxp\Helper\ArrayHelper::objectToArray()" instead.');

    return Helper\ArrayHelper::objectToArray($node);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function array_urlencode(array $args): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "array_urlencode()" is deprecated, use "\http_build_query()" instead.');

    return http_build_query($args);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function array_toquerystring(array $args): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "array_toquerystring()" is deprecated, use "\OpenDxp\Helper\ArrayHelper::arrayToQueryString()" instead.');

    return Helper\ArrayHelper::arrayToQueryString($args);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * @param array $array with attribute names as keys, and values as values
 */
function array_to_html_attribute_string(array $array): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "array_to_html_attribute_string()" is deprecated, use "\OpenDxp\Helper\StringHelper::arrayToHtmlAttributeString()" instead.');

    return Helper\StringHelper::arrayToHtmlAttributeString($array);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function urlencode_ignore_slash(string $var): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "urlencode_ignore_slash()" is deprecated, use "\OpenDxp\Helper\StringHelper::urlEncodeIgnoreSlash()" instead.');

    return Helper\StringHelper::urlEncodeIgnoreSlash($var);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function return_bytes(string $val): int
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling return_bytes()" is deprecated and will be removed in 2.0.');

    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $bytes = (int)$val;
    switch ($last) {
        case 'g':
            $bytes *= 1024;
            // no break
        case 'm':
            $bytes *= 1024;
            // no break
        case 'k':
            $bytes *= 1024;
    }

    return $bytes;
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "formatBytes()" is deprecated, use "\OpenDxp\Helper\FileSystemHelper::formatBytes()" instead.');

    return Helper\FileSystemHelper::formatBytes($bytes, $precision);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function filesize2bytes(string $str): int
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "filesize2bytes()" is deprecated, use "\OpenDxp\Helper\FileSystemHelper::filesizeToBytes()" instead.');

    return Helper\FileSystemHelper::filesizeToBytes($str);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * @param string[] $data
 *
 * @return string[]
 */
function rscandir(string $base = '', array &$data = []): array
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "rscandir()" is deprecated, use "\OpenDxp\Helper\FileSystemHelper::scanDirectory()" instead.');

    return Helper\FileSystemHelper::scanDirectory($base);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * Wrapper for explode() to get a trimmed array
 *
 * @return string[]
 *
 * @phpstan-param non-empty-string $delimiter
 */
function explode_and_trim(string $delimiter, string $string, int $limit = PHP_INT_MAX, bool $useArrayFilter = true): array
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "explode_and_trim()" is deprecated, use "\OpenDxp\Helper\ArrayHelper::explodeAndTrim()" instead.');

    return Helper\ArrayHelper::explodeAndTrim($delimiter, $string, $limit, $useArrayFilter);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function recursiveDelete(string $directory, bool $empty = true): bool
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "recursiveDelete()" is deprecated, use "\OpenDxp\Helper\FileSystemHelper::recursiveDelete()" instead.');

    return Helper\FileSystemHelper::recursiveDelete($directory, $empty);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function p_r(): void
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling p_r()" is deprecated and will be removed in 2.0.');

    $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
    $dumper = 'cli' === PHP_SAPI ? new \Symfony\Component\VarDumper\Dumper\CliDumper() : new \Symfony\Component\VarDumper\Dumper\HtmlDumper();

    foreach (func_get_args() as $var) {
        $dumper->dump($cloner->cloneVar($var));
    }
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * @return string[]
 */
function wrapArrayElements(array $array, string $prefix = "'", string $suffix = "'"): array
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "wrapArrayElements()" is deprecated and will be removed in 2.0.');

    foreach ($array as $key => $value) {
        $array[$key] = $prefix . trim($value). $suffix;
    }

    return $array;
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * Checks if an array is associative
 */
function isAssocArray(array $arr): bool
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling wrapArrayElements()" is deprecated, use "!\array_is_list($arr)" instead.');

    return !array_is_list($arr);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * this is an alternative for realpath() which isn't able to handle symlinks correctly
 */
function resolvePath(string $filename): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "resolvePath()" is deprecated and will be removed in 2.0.');

    $protocol = '';
    if (!stream_is_local($filename)) {
        $protocol = parse_url($filename, PHP_URL_SCHEME) . '://';
        $filename = str_replace($protocol, '', $filename);
    }

    $filename = str_replace('//', '/', $filename);
    $parts = explode('/', $filename);
    $out = [];
    foreach ($parts as $part) {
        if ($part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($out);

            continue;
        }
        $out[] = $part;
    }

    return $protocol . implode('/', $out);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function closureHash(Closure $closure): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "closureHash()" is deprecated, use "\OpenDxp\Helper\StringHelper::closureHash()" instead.');

    return Helper\StringHelper::closureHash($closure);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 *
 * Checks if the given directory is empty
 */
function is_dir_empty(string $dir): ?bool
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "is_dir_empty()" is deprecated, use "\OpenDxp\Helper\FileSystemHelper::isDirEmpty()" instead.');

    return Helper\FileSystemHelper::isDirEmpty($dir);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function var_export_pretty(mixed $var, string $indent = ''): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "var_export_pretty()" is deprecated, use "\OpenDxp\Helper\ExportHelper::varExportPretty()" instead.');

    return Helper\ExportHelper::varExportPretty($var, $indent);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function to_php_data_file_format(mixed $contents, ?string $comments = null): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "to_php_data_file_format()" is deprecated, use "\OpenDxp\Helper\ExportHelper::toPhpDataFileFormat()" instead.');

    return Helper\ExportHelper::toPhpDataFileFormat($contents, $comments);
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function generateRandomSymfonySecret(): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "generateRandomSymfonySecret()" is deprecated, use "\OpenDxp\Helper\StringHelper::generateRandomSymfonySecret()" instead.');

    return Helper\StringHelper::generateRandomSymfonySecret();
}

/**
 * @deprecated since OpenDXP 1.2 and will be removed in 2.0
 */
function implode_recursive(array $array, string $glue): string
{
    trigger_deprecation('open-dxp/opendxp', '1.2', 'Calling "implode_recursive()" is deprecated, use "\OpenDxp\Helper\StringHelper::implodeRecursive()" instead.');

    return Helper\StringHelper::implodeRecursive($array, $glue);
}
