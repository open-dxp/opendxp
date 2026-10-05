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

function handled(AssetPreviewImageMessage $message): ?Throwable
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
    $handler->flush(true);

    return $error;
}

function previewOf(Asset $asset): Asset\Image\Thumbnail
{
    return $asset->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig());
}

it('writes the preview image of an asset it is handed', function () {

    $asset = AssetImageFactory::createOne();

    expect(previewOf($asset)->exists())->toBeFalse();

    expect(handled(new AssetPreviewImageMessage($asset->getId())))->toBeNull();

    expect(previewOf($asset)->exists())->toBeTrue();
});

it('reports back the failure when the asset is no image at all', function () {

    $asset = AssetImageFactory::createOne(['data' => 'this-is-not-a-valid-image']);

    expect(handled(new AssetPreviewImageMessage($asset->getId())))
        ->toBeInstanceOf(ThumbnailGenerationFailedException::class)
        ->and(previewOf($asset)->exists())
        ->toBeFalse();
});
