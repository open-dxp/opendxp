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

class ExportHelper
{
    public static function varExportPretty(mixed $var, string $indent = ''): string
    {
        switch (gettype($var)) {
            case 'string':
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case 'array':
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? '' : self::varExportPretty($key) . ' => ')
                        . self::varExportPretty($value, "$indent    ");
                }

                return "[\n" . implode(",\n", $r) . "\n" . $indent . ']';
            case 'boolean':
                return $var ? 'TRUE' : 'FALSE';
            default:
                return var_export($var, true);
        }
    }

    public static function toPhpDataFileFormat(mixed $contents, ?string $comments = null): string
    {
        $contents = self::varExportPretty($contents);

        $export = '<?php';

        if (!empty($comments)) {
            $export .= "\n\n";
            $export .= $comments;
            $export .= "\n";
        }

        return $export . ("\n\nreturn " . $contents . ";\n");
    }
}
