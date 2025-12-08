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

namespace OpenDxp\Model\Asset\WebDAV;

use Exception;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use Override;
use Sabre\DAV;

/**
 * @internal
 */
class Tree extends DAV\Tree
{
    /**
     * Moves a file/directory
     *
     * @param string $sourcePath
     * @param string $destinationPath
     */
    #[Override]
    public function move($sourcePath, $destinationPath): void
    {
        $nameParts = explode('/', $sourcePath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $sourcePath = implode('/', $nameParts);

        $nameParts = explode('/', $destinationPath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $destinationPath = implode('/', $nameParts);

        try {
            if (dirname($sourcePath) === dirname($destinationPath)) {
                $asset = null;

                if ($asset = Asset::getByPath('/' . $destinationPath)) {
                    // If we got here, this means the destination exists, and needs to be overwritten
                    $sourceAsset = Asset::getByPath('/' . $sourcePath);
                    $asset->setData($sourceAsset->getData());
                    $sourceAsset->delete();
                }

                // see: Asset\WebDAV\File::delete() why this is necessary
                $log = Asset\WebDAV\Service::getDeleteLog();
                if (!$asset && array_key_exists('/' .$destinationPath, $log)) {
                    $asset = \OpenDxp\Tool\Serialize::unserialize($log['/' .$destinationPath]['data']);
                    if ($asset) {
                        $sourceAsset = Asset::getByPath('/' . $sourcePath);
                        $asset->setData($sourceAsset->getData());
                        $sourceAsset->delete();
                    }
                }

                if (!$asset) {
                    $asset = Asset::getByPath('/' . $sourcePath);
                }
                $asset->setFilename(basename($destinationPath));
            } else {
                $asset = Asset::getByPath('/' . $sourcePath);
                $parent = Asset::getByPath('/' . dirname($destinationPath));

                $asset->setPath($parent->getRealFullPath() . '/');
                $asset->setParentId($parent->getId());
            }

            $user = \OpenDxp\Tool\Admin::getCurrentUser();
            $asset->setUserModification($user->getId());
            $asset->save();
        } catch (Exception $e) {
            Logger::error((string) $e);
        }
    }
}
