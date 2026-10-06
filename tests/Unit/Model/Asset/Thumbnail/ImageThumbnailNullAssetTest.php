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

namespace OpenDxp\Tests\Unit\Model\Asset\Thumbnail;

use OpenDxp\Model\Asset\Document\ImageThumbnail as DocumentImageThumbnail;
use OpenDxp\Model\Asset\Video\ImageThumbnail as VideoImageThumbnail;

it('has no asset when it is built without one', function (VideoImageThumbnail|DocumentImageThumbnail $thumbnail) {
    $asset = $thumbnail->getAsset();

    expect($asset)->toBeNull();
})->with([
    'a video thumbnail' => fn () => new VideoImageThumbnail(asset: null),
    'a document thumbnail' => fn () => new DocumentImageThumbnail(asset: null),
]);
