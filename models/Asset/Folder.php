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

namespace OpenDxp\Model\Asset;

use OpenDxp;
use OpenDxp\Db\Helper;
use OpenDxp\File;
use OpenDxp\Logger;
use OpenDxp\Messenger\AssetPreviewImageMessage;
use OpenDxp\Model;
use OpenDxp\Model\Asset;
use OpenDxp\Tool\Storage;

/**
 * @method \OpenDxp\Model\Asset\Dao getDao()
 */
class Folder extends Model\Asset
{
    protected string $type = 'folder';

    /**
     * @internal
     */
    protected ?Listing $children = null;

    /**
     * set the children of the document
     *
     *
     * @return $this
     */
    public function setChildren(?Listing $children): static
    {
        $this->children = $children;

        return $this;
    }

    #[\Override]
    public function getChildren(): Listing
    {
        if (!$this->children instanceof \OpenDxp\Model\Asset\Listing) {
            if ($this->getId()) {
                $list = new Asset\Listing();
                $list->setCondition('parentId = ?', $this->getId());
                $list->setOrderKey('filename');
                $list->setOrder('asc');

                $this->children = $list;
            } else {
                $list = new Listing();
                $list->setAssets([]);
                $this->children = $list;
            }
        }

        return $this->children;
    }

    #[\Override]
    public function hasChildren(): bool
    {
        return $this->getDao()->hasChildren();
    }

    /**
     * @internal
     *
     * @return resource|null
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \League\Flysystem\FilesystemException
     */
    public function getPreviewImage(bool $force = false)
    {
        $storage = Storage::get('thumbnail');
        $cacheFilePath = sprintf(
            '%s/%s/image-thumb__%s__-folder-preview%s.jpg',
            rtrim($this->getRealPath(), '/'),
            $this->getId(),
            $this->getId(),
            '-hdpi'
        );

        $tileThumbnailConfig = Asset\Image\Thumbnail\Config::getPreviewConfig();

        $limit = 42;
        $db = \OpenDxp\Db::get();
        $condition = "`path` LIKE :path AND `type` IN ('image', 'video', 'document')";
        $conditionParams = [
            'path' => Helper::escapeLike($this->getRealFullPath()) . '/%',
        ];

        if ($storage->fileExists($cacheFilePath)) {
            $lastUpdate = $db->fetchOne('SELECT MAX(modificationDate) FROM assets WHERE ' . $condition . ' ORDER BY filename ASC LIMIT ' . $limit, $conditionParams);
            if ($lastUpdate < $storage->lastModified($cacheFilePath)) {
                return $storage->readStream($cacheFilePath);
            }
        }

        $list = new Asset\Listing();
        $list->setCondition($condition, $conditionParams);
        $list->setOrderKey('id');
        $list->setOrder('asc');
        $list->setLimit($limit);

        $totalImages = $list->getCount();
        $count = 0;
        $gutter = 5;
        $squareDimension = 130;
        $offsetTop = 0;
        $colums = 3;
        $skipped = false;

        if ($totalImages) {
            $collage = imagecreatetruecolor(($squareDimension * $colums) + ($gutter * ($colums - 1)), (int) ceil(($totalImages / $colums)) * ($squareDimension + $gutter));
            $background = imagecolorallocate($collage, 12, 15, 18);
            imagefill($collage, 0, 0, $background);

            foreach ($list as $asset) {
                if ($asset instanceof Document && !$asset->getPageCount()) {
                    continue;
                }

                $offsetLeft = ($squareDimension + $gutter) * ($count % $colums);
                $tileThumb = null;
                if ($asset instanceof Image) {
                    $tileThumb = $asset->getThumbnail($tileThumbnailConfig);
                } elseif ($asset instanceof Document || $asset instanceof Video) {
                    $tileThumb = $asset->getImageThumbnail($tileThumbnailConfig);
                }

                if ($tileThumb) {
                    if (!$tileThumb->exists() && !$force) {
                        // only generate if all necessary thumbs are available
                        $skipped = true;

                        OpenDxp::getContainer()->get('messenger.bus.opendxp-core')->dispatch(
                            new AssetPreviewImageMessage($this->getId())
                        );

                        break;
                    }

                    $stream = $tileThumb->getStream();

                    if (null === $stream) {
                        break;
                    }

                    $width = $tileThumb->getWidth();
                    $height = $tileThumb->getHeight();
                    if (!$width || !$height) {
                        break;
                    }

                    $tile = imagecreatefromstring(stream_get_contents($stream));
                    imagecopyresampled($collage, $tile, $offsetLeft, $offsetTop, 0, 0, $squareDimension, $squareDimension, $width, $height);

                    $count++;
                    if ($count % $colums === 0) {
                        $offsetTop += ($squareDimension + $gutter);
                    }
                }
            }

            if ($count && !$skipped) {
                $localFile = File::getLocalTempFilePath('jpg');
                if (false === imagejpeg($collage, $localFile, 60)) {
                    Logger::info('Creation of collage file of asset folder ' . $this->getRealFullPath() . ' is failed.');

                    return null;
                }
                $localFileContent = file_get_contents($localFile);
                if (false === $localFileContent) {
                    Logger::info('Generated collage file of asset folder ' . $this->getRealFullPath() . ' is broken or cannot be found.');

                    return null;
                }
                $storage->write($cacheFilePath, $localFileContent);

                return $storage->readStream($cacheFilePath);
            }
        }

        return null;
    }
}
