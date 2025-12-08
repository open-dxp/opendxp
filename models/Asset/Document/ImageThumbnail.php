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

namespace OpenDxp\Model\Asset\Document;

use Exception;
use OpenDxp;
use OpenDxp\Document;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\FrontendEvents;
use OpenDxp\File;
use OpenDxp\Helper\TemporaryFileHelperTrait;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Model\Exception\ThumbnailFormatNotSupportedException;
use OpenDxp\Tool\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\LockFactory;

/**
 * @property Model\Asset\Document|null $asset
 */
final class ImageThumbnail implements ImageThumbnailInterface
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;
    use TemporaryFileHelperTrait;

    public function __construct(
        ?Model\Asset\Document $asset,
        array|string|Image\Thumbnail\Config|null $config = null,
        /**
         * @internal
         */
        protected int $page = 1,
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
        OpenDxp::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_DOCUMENT_IMAGE_THUMBNAIL);

        return $event->getArgument('frontendPath');
    }

    /**
     * @throws ThumbnailFormatNotSupportedException
     */
    public function generate(bool $deferredAllowed = true): void
    {
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if ($this->asset && $this->pathReference === []) {

            if (!$this->checkAllowedFormats($this->config->getFormat(), $this->asset)) {
                throw new ThumbnailFormatNotSupportedException();
            }

            $config = $this->getConfig();
            $cacheFileStream = null;
            $config->setFilenameSuffix('page-' . $this->page);

            try {
                if (!$deferred && $cacheFileStream = $this->getCacheFileStream()) {
                    $generated = true;
                }

                if ($config && ($deferred || $cacheFileStream)) {
                    $this->pathReference = Image\Thumbnail\Processor::process($this->asset, $config, $cacheFileStream, $deferred, $generated);
                }
            } catch (Exception $e) {
                Logger::error("Couldn't create image-thumbnail of document " . $this->asset->getRealFullPath() . ': ' . $e);
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

        OpenDxp::getEventDispatcher()->dispatch($event, AssetEvents::DOCUMENT_IMAGE_THUMBNAIL);
    }

    /**
     * @return resource|null
     */
    private function getCacheFileStream()
    {
        if (!$this->asset instanceof Model\Asset\Document) {
            Logger::info('Cannot create cache file stream: asset is not of type document');

            return null;
        }

        $storage = Storage::get('asset_cache');

        $cacheFilePath = sprintf(
            '%s/%s/image-thumb__%s__document_original_image/page_%s.png',
            rtrim($this->asset->getRealPath(), '/'),
            $this->asset->getId(),
            $this->asset->getId(),
            $this->page
        );

        if (!$storage->fileExists($cacheFilePath)) {
            $lock = OpenDxp::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
            if ($lock->acquire()) {
                $tempFile = File::getLocalTempFilePath('png');

                try {
                    $converter = Document::getInstance();
                    $converter->load($this->asset);

                    if (false === $converter->saveImage($tempFile, $this->page)) {
                        Logger::info('Creation of cache file stream of document ' . $this->asset->getRealFullPath() . ' is failed.');

                        return null;
                    }

                    $tempFileContent = file_get_contents($tempFile);
                    if (false === $tempFileContent) {
                        Logger::info('Creation of cache file stream of document ' . $this->asset->getRealFullPath() . ' is failed.');

                        return null;
                    }
                    $storage->write($cacheFilePath, $tempFileContent);
                } finally {
                    $lock->release();
                }
            } else {
                Logger::info('Creation of cache file stream of document ' . $this->asset->getRealFullPath() . ' is locked');

                return null;
            }
        }

        return $storage->readStream($cacheFilePath);
    }

    /**
     * Get the public path to the thumbnail image.
     * This method is here for backwards compatility.
     *
     * @return string Public path to thumbnail image.
     */
    public function __toString(): string
    {
        return $this->getPath();
    }

    protected function createConfig(array|string|Image\Thumbnail\Config $selector): Image\Thumbnail\Config
    {
        $config = Image\Thumbnail\Config::getByAutoDetect($selector);

        if (!empty($selector) && !$config instanceof \OpenDxp\Model\Asset\Image\Thumbnail\Config) {
            throw new NotFoundException('Thumbnail definition "' . (is_string($selector) ? $selector : '') . '" does not exist');
        }

        if ($config) {
            $format = strtolower($config->getFormat());
            if ($format === 'source') {
                $config->setFormat('PNG');
            }
        }

        return $config;
    }
}
