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

namespace OpenDxp\HttpKernel\CacheWarmer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Creates needed opendxp directories when warming up the cache
 *
 * @internal
 */
class MkdirCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private readonly int $mode = 0775)
    {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $directories = [
            // var
            OPENDXP_CLASS_DIRECTORY,
            OPENDXP_CONFIGURATION_DIRECTORY,
            OPENDXP_LOG_DIRECTORY,
            OPENDXP_SYSTEM_TEMP_DIRECTORY,
        ];

        // Since #12392, OPENDXP_CLASS_DEFINITION_WRITABLE = 0 doesn't allow creation in var/classes but is allowed when not set or 1.
        if (true == ($_SERVER['OPENDXP_CLASS_DEFINITION_WRITABLE'] ?? true)) {
            $directories[] = OPENDXP_CLASS_DEFINITION_DIRECTORY;
        }

        $fs = new Filesystem();
        foreach (array_unique($directories) as $directory) {
            if (!$fs->exists($directory)) {
                $fs->mkdir($directory, $this->mode);
            }
        }

        return [];
    }
}
