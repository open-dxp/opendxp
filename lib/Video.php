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
class Video
{
    /**
     *
     *
     * @throws Exception
     */
    public static function getInstance(?string $adapter = null): ?Video\Adapter
    {
        try {
            if ($adapter) {
                $adapterClass = '\\OpenDxp\\Video\\Adapter\\' . $adapter;
                if (Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                }

                throw new Exception('Video-transcode adapter `' . $adapter . '´ does not exist.');
            }
            if ($adapter = self::getDefaultAdapter()) {
                return $adapter;
            }
        } catch (Exception $e) {
            Logger::crit('Unable to load video adapter: ' . $e->getMessage());

            throw $e;
        }

        return null;
    }

    public static function isAvailable(): bool
    {
        return self::getDefaultAdapter() instanceof \OpenDxp\Video\Adapter;
    }

    private static function getDefaultAdapter(): ?Video\Adapter
    {
        $adapters = ['Ffmpeg'];

        foreach ($adapters as $adapter) {
            $adapterClass = '\\OpenDxp\\Video\\Adapter\\' . $adapter;
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
