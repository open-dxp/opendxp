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

namespace OpenDxp\Model\Asset\WebDAV;

use OpenDxp\Model\Asset;
use OpenDxp\Tool\Serialize;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class Service
{
    public static function getDeleteLogFile(): string
    {
        return OPENDXP_SYSTEM_TEMP_DIRECTORY . '/webdav-delete.dat';
    }

    public static function getDeleteLog(): array
    {
        if (!file_exists(self::getDeleteLogFile())) {
            return [];
        }

        $log = Serialize::unserialize(
            file_get_contents(self::getDeleteLogFile()),
            ['allowed_classes' => false]
        );

        if (!is_array($log)) {
            return [];
        }

        $cutoff = time() - 30;

        return array_filter($log, static fn (array $data) => $data['timestamp'] > $cutoff);
    }

    public static function saveDeleteLog(array $log): void
    {
        $cutoff = time() - 30;
        $log = array_filter($log, static fn (array $data) => $data['timestamp'] > $cutoff);

        $filesystem = new Filesystem();
        $filesystem->dumpFile(self::getDeleteLogFile(), Serialize::serialize($log));
    }

    /**
     * File::delete() dumps the whole asset into the log entry read here, so Tree::move() can
     * restore it. Dump state keeps the asset's properties and children instead of stripping
     * them. What a property or a child asset actually holds depends on the types this project
     * configures, so there's no fixed set of classes this method could check against. The log
     * is only ever written by File::delete(), so reading it back carries no more risk than any
     * other data this class already persists.
     */
    public static function restoreDeletedAsset(string $payload): ?Asset
    {
        $asset = Serialize::unserialize($payload, ['allowed_classes' => true]);

        return $asset instanceof Asset ? $asset : null;
    }
}
