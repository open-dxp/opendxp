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
use OpenDxp\Tests\Support\Test\TestCase;

/**
 * A thumbnail may be constructed without a backing asset (its `$asset` property is
 * nullable). `getAsset()` must therefore be able to return `null` instead of violating
 * its own return type with a `TypeError`.
 */
class ImageThumbnailNullAssetTest extends TestCase
{
    public function testVideoImageThumbnailWithoutAssetReturnsNull(): void
    {
        $thumbnail = new VideoImageThumbnail(null);

        $this->assertNull($thumbnail->getAsset());
    }

    public function testDocumentImageThumbnailWithoutAssetReturnsNull(): void
    {
        $thumbnail = new DocumentImageThumbnail(null);

        $this->assertNull($thumbnail->getAsset());
    }
}
