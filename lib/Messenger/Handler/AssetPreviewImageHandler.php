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

namespace OpenDxp\Messenger\Handler;

use OpenDxp\Exception\ThumbnailGenerationFailedException;
use OpenDxp\Messenger\AssetPreviewImageMessage;
use OpenDxp\Model\Asset;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class AssetPreviewImageHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(AssetPreviewImageMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    private function process(array $jobs): void
    {
        foreach ($jobs as [$message, $ack]) {
            try {
                $asset = Asset::getById($message->getId());

                $thumbnail = null;
                if ($asset instanceof Asset\Image) {
                    $thumbnail = $asset->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig());
                } elseif ($asset instanceof Asset\Document || $asset instanceof Asset\Video) {
                    $thumbnail = $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig());
                } elseif ($asset instanceof Asset\Folder) {
                    // no exists() verification needed here: getPreviewImage() redispatches
                    // itself on read while tile thumbnails are still missing
                    $asset->getPreviewImage(true);
                }

                if ($thumbnail !== null) {
                    $thumbnail->generate(false);

                    if (!$thumbnail->exists()) {
                        // generation errors are caught and logged inside generate(), the path reference
                        // then points to the "filetype not supported" placeholder instead of a thumbnail
                        throw new ThumbnailGenerationFailedException(sprintf(
                            'Unable to generate preview image thumbnail for asset %d, see previous log entries for details',
                            $message->getId()
                        ));
                    }
                }

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }

    private function shouldFlush(): bool
    {
        return 5 <= count($this->jobs);
    }
}
