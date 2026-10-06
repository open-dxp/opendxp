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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Feature\Messenger;

use OpenDxp\Exception\ThumbnailGenerationFailedException;
use OpenDxp\Messenger\AssetPreviewImageMessage;
use OpenDxp\Messenger\Handler\AssetPreviewImageHandler;
use OpenDxp\Model\Asset;
use OpenDxp\Test\Factory\AssetImageFactory;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Throwable;

function errorOnHandling(AssetPreviewImageMessage $message): ?Throwable
{
    $error = null;

    $acknowledger = new Acknowledger(
        AssetPreviewImageHandler::class,
        static function (?Throwable $thrown) use (&$error): void {
            $error = $thrown;
        },
    );

    $handler = new AssetPreviewImageHandler(new NullLogger());
    $handler($message, $acknowledger);
    $handler->flush(force: true);

    return $error;
}

function previewOf(Asset $asset): Asset\Image\Thumbnail
{
    return $asset->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig());
}

it('writes the preview image of an asset', function () {
    $asset = AssetImageFactory::createOne();
    // Thumbnails live outside the transaction. A run with the same faker seed finds the files of the last one.
    $asset->clearThumbnails(force: true);

    $error = errorOnHandling(new AssetPreviewImageMessage($asset->getId()));

    expect($error)
        ->toBeNull()
        ->and(previewOf($asset)->exists())
        ->toBeTrue();
});

it('reports the failure for an asset that is no image', function () {
    $asset = AssetImageFactory::createOne(['data' => 'no image']);

    $error = errorOnHandling(new AssetPreviewImageMessage($asset->getId()));

    expect($error)
        ->toBeInstanceOf(ThumbnailGenerationFailedException::class)
        ->and(previewOf($asset)->exists())
        ->toBeFalse();
});
