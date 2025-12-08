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

namespace OpenDxp;

use Exception;

/**
 * @internal
 */
class Document
{
    /**
     * Singleton for OpenDxp\Document
     *
     * @throws Exception
     */
    public static function getInstance(?string $adapter = null): ?Document\Adapter
    {
        try {
            if ($adapter) {

                $adapterClass = '\\OpenDxp\\Document\\Adapter\\' . $adapter;
                if (Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                }

                throw new Exception('document-transcode adapter `' . $adapter . '´ does not exist.');
            }

            if ($adapter = self::getDefaultAdapter()) {
                return $adapter;
            }

        } catch (Exception $e) {
            Logger::crit('Unable to load document adapter: ' . $e->getMessage());

            throw $e;
        }

        return null;
    }

    /**
     * Checks if adapter is available.
     */
    public static function isAvailable(): bool
    {
        return self::getDefaultAdapter() instanceof \OpenDxp\Document\Adapter;
    }

    /**
     * Checks if a file type is supported by the adapter.
     */
    public static function isFileTypeSupported(string $filetype): bool
    {
        if ($adapter = self::getDefaultAdapter()) {
            return $adapter->isFileTypeSupported($filetype);
        }

        return false;
    }

    /**
     * Returns adapter class if exists or false if doesn't exist
     */
    public static function getDefaultAdapter(): ?Document\Adapter
    {
        $adapters = ['Gotenberg', 'LibreOffice', 'Ghostscript'];

        foreach ($adapters as $adapter) {
            $adapterClass = '\\OpenDxp\\Document\\Adapter\\' . $adapter;
            if (Tool::classExists($adapterClass)) {
                try {
                    $adapter = new $adapterClass();
                    if ($adapter->isAvailable()) {
                        return $adapter;
                    }
                } catch (Exception $e) {
                    Logger::warning((string) $e);
                }
            }
        }

        return null;
    }
}
