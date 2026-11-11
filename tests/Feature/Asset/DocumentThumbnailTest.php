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

namespace OpenDxp\Tests\Feature\Asset;

use OpenDxp\Model\Asset\Image\Thumbnail\Config;
use OpenDxp\Test\Factory\AssetDocumentFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;

it('renders a PDF page inside its CropBox when the thumbnail asks for it', function () {
    $document = AssetDocumentFactory::createOne([
        'data' => file_get_contents(dirname(__DIR__, 2) . '/Fixtures/cropbox.pdf'),
    ]);

    $sheet = ThumbnailConfigFactory::createOne();
    $cropBox = ThumbnailConfigFactory::createOne(['useCropBox' => true]);

    $sheetWidth = $document->getImageThumbnail($sheet, deferred: false)->getWidth();
    $cropBoxWidth = $document->getImageThumbnail(Config::getByName($cropBox->getName()), deferred: false)->getWidth();

    expect($cropBoxWidth)->toBeLessThan($sheetWidth);
});
