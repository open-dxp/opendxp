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

use FilesystemIterator;

class FileSystemHelper
{
    public static function gzCompressFile(string $source, ?int $level = null, ?string $target = null): false|string
    {
        if (!is_file($source) || !is_readable($source)) {
            return false;
        }

        $dest = $target ?: $source . '.gz';

        if ($level !== null) {
            $level = max(0, min(9, $level));
            $mode = "wb{$level}";
        } else {
            $mode = 'wb';
        }

        $fpOut = @gzopen($dest, $mode);
        if ($fpOut === false) {
            return false;
        }

        $fpIn = @fopen($source, 'rb');
        if ($fpIn === false) {
            gzclose($fpOut);

            return false;
        }

        $chunkSize = 1024 * 512;

        while (!feof($fpIn)) {

            $data = fread($fpIn, $chunkSize);

            if ($data === false) {
                fclose($fpIn);
                gzclose($fpOut);

                return false;
            }

            gzwrite($fpOut, $data);
        }

        fclose($fpIn);
        gzclose($fpOut);

        return $dest;
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1000 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function filesizeToBytes(string $str): int
    {
        $bytes = (float) $str;

        if (preg_match('#([KMGTP])B?$#i', $str, $matches)) {
            $bytes *= match (strtoupper($matches[1])) {
                'K' => 1024,
                'M' => 1024 ** 2,
                'G' => 1024 ** 3,
                'T' => 1024 ** 4,
                'P' => 1024 ** 5,
                default => 1,
            };
        }

        return (int) round($bytes, 2);
    }

    /**
     * @return string[]
     */
    public static function scanDirectory(string $base = ''): array
    {
        $data = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $data[] = $file->getPathname() . ($file->isDir() ? DIRECTORY_SEPARATOR : '');
        }

        return $data;
    }

    public static function recursiveDelete(string $directory, bool $empty = true): bool
    {
        if (is_dir($directory)) {

            $directory = rtrim($directory, '/');

            if (!file_exists($directory) || !is_dir($directory)) {
                return false;
            }
            if (!is_readable($directory)) {
                return false;
            }

            $directoryHandle = opendir($directory);
            $contents = '.';

            while ($contents) {
                $contents = readdir($directoryHandle);
                if ($contents !== false && $contents !== '.' && $contents !== '..') {
                    $path = $directory . '/' . $contents;

                    if (is_dir($path)) {
                        self::recursiveDelete($path);
                    } else {
                        unlink($path);
                    }
                }
            }

            closedir($directoryHandle);

            return !($empty && !rmdir($directory));
        }

        if (is_file($directory)) {
            return unlink($directory);
        }

        return false;
    }

    public static function isDirEmpty(string $dir): ?bool
    {
        if (!is_readable($dir)) {
            return null;
        }

        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry !== '.' && $entry !== '..') {
                return false;
            }
        }

        return true;
    }
}
