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

namespace OpenDxp\Model\Asset\Video;

use Exception;
use OpenDxp;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\FrontendEvents;
use OpenDxp\File;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Exception\ThumbnailFormatNotSupportedException;
use OpenDxp\Tool\Storage;
use OpenDxp\Video;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\LockFactory;

/**
 * @property Model\Asset\Video|null $asset
 */
final class ImageThumbnail implements ImageThumbnailInterface
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;

    public function __construct(
        ?Model\Asset\Video $asset,
        array|string|Image\Thumbnail\Config|null $config = null,
        protected ?int $timeOffset = null,
        protected ?Image $imageAsset = null,
        bool $deferred = true
    ) {
        $this->asset = $asset;
        $this->config = $this->createConfig($config ?? []);
        $this->deferred = $deferred;
    }

    public function getPath(array $args = []): string
    {
        // set defaults
        $deferredAllowed = $args['deferredAllowed'] ?? true;
        $frontend = $args['frontend'] ??\OpenDxp\Tool::isFrontend();

        $pathReference = $this->getPathReference($deferredAllowed);

        $path = $this->convertToWebPath($pathReference, $frontend);

        $event = new GenericEvent($this, [
            'pathReference' => $pathReference,
            'frontendPath' => $path,
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_VIDEO_IMAGE_THUMBNAIL);

        return $event->getArgument('frontendPath');
    }

    /**
     * @throws Exception|\League\Flysystem\FilesystemException|ThumbnailFormatNotSupportedException
     *
     * @internal
     */
    public function generate(bool $deferredAllowed = true): void
    {
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if ($this->asset && $this->pathReference === []) {

            if (!$this->checkAllowedFormats($this->config->getFormat(), $this->asset)) {
                throw new ThumbnailFormatNotSupportedException();
            }

            $cs = $this->asset->getCustomSetting('image_thumbnail_time');
            $im = $this->asset->getCustomSetting('image_thumbnail_asset');

            if ($im || $this->imageAsset) {
                $im = $this->imageAsset ?: Model\Asset::getById((int) $im);

                if ($im instanceof Image) {
                    $imageThumbnail = $im->getThumbnail($this->getConfig());
                    $this->pathReference = $imageThumbnail->getPathReference();
                }
            }

            if (!$this->pathReference) {
                $timeOffset = $this->timeOffset;
                if (!is_numeric($timeOffset) && is_numeric($cs)) {
                    $timeOffset = $cs;
                }

                // fallback
                if (!is_numeric($timeOffset) && $this->asset instanceof Model\Asset\Video) {
                    $timeOffset = ceil($this->asset->getDuration() / 3);
                }

                $storage = Storage::get('asset_cache');
                $cacheFilePath = sprintf(
                    '%s/%s/image-thumb__%s__video_original_image/time_%s.png',
                    rtrim($this->asset->getRealPath(), '/'),
                    $this->asset->getId(),
                    $this->asset->getId(),
                    $timeOffset
                );

                if (!$storage->fileExists($cacheFilePath)) {
                    $lock = OpenDxp::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
                    $lock->acquire(true);

                    // after we got the lock, check again if the image exists in the meantime - if not - generate it
                    if (!$storage->fileExists($cacheFilePath)) {
                        $tempFile = File::getLocalTempFilePath('png');
                        $converter = Video::getInstance();
                        $converter->load($this->asset->getLocalFile());
                        if (false === $converter->saveImage($tempFile, (int) $timeOffset)) {
                            Logger::info('Creation of cache file stream of document ' . $this->asset->getRealFullPath() . ' is failed.');

                            return;
                        }
                        $tempFileContent = file_get_contents($tempFile);
                        if (false === $tempFileContent) {
                            Logger::info('Creation of cache file stream of document ' . $this->asset->getRealFullPath() . ' is failed.');

                            return;
                        }
                        $storage->write($cacheFilePath, $tempFileContent);
                        $generated = true;
                    }

                    $lock->release();
                }

                $cacheFileStream = $storage->readStream($cacheFilePath);

                if ($this->getConfig()) {
                    $this->getConfig()->setFilenameSuffix('time-' . $timeOffset);

                    try {
                        $this->pathReference = Image\Thumbnail\Processor::process(
                            $this->asset,
                            $this->getConfig(),
                            $cacheFileStream,
                            $deferred,
                            $generated
                        );
                    } catch (Exception $e) {
                        Logger::error("Couldn't create image-thumbnail of video " . $this->asset->getRealFullPath() . ': ' . $e);
                    }
                }
            }
        }

        if ($this->pathReference === []) {
            $this->pathReference = [
                'type' => 'error',
                'src' => '/bundles/opendxpadmin/img/filetype-not-supported.svg',
            ];
        }

        $event = new GenericEvent($this, [
            'deferred' => $deferred,
            'generated' => $generated,
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, AssetEvents::VIDEO_IMAGE_THUMBNAIL);
    }

    /**
     * Get the public path to the thumbnail image.
     * This OpenDxp is here for backwards compatility.
     * Up to OpenDxp 1.4.8 a thumbnail was returned as a path to an image.
     *
     * @return string Public path to thumbnail image.
     */
    public function __toString(): string
    {
        return $this->getPath();
    }

    /**
     * @throws Model\Exception\NotFoundException
     */
    private function createConfig(array|string|Image\Thumbnail\Config $selector): ?Image\Thumbnail\Config
    {
        $thumbnailConfig = Image\Thumbnail\Config::getByAutoDetect($selector);

        if (!empty($selector) && !$thumbnailConfig instanceof \OpenDxp\Model\Asset\Image\Thumbnail\Config) {
            throw new Model\Exception\NotFoundException('Thumbnail definition "' . (is_string($selector) ? $selector : '') . '" does not exist');
        }

        return $thumbnailConfig;
    }

    /**
     * @throws Exception
     */
    public function getMedia(string $name, int $highRes = 1): ?Image\ThumbnailInterface
    {
        $thumbConfig = $this->getConfig();
        if ($thumbConfig instanceof Image\Thumbnail\Config) {
            $mediaConfigs = $thumbConfig->getMedias();

            if (isset($mediaConfigs[$name])) {
                $thumbConfigRes = clone $thumbConfig;
                $thumbConfigRes->selectMedia($name);
                $thumbConfigRes->setHighResolution($highRes);
                $thumbConfigRes->setMedias([]);
                $imgId = $this->asset->getCustomSetting('image_thumbnail_asset');
                $img = Model\Asset::getById((int) $imgId);

                if ($img instanceof Image) {
                    $thumb = $img->getThumbnail($thumbConfigRes);
                }

                return $thumb ?? null;
            }

            throw new Exception("Media query '" . $name . "' doesn't exist in thumbnail configuration: " . $thumbConfig->getName());
        }

        return null;
    }
}
