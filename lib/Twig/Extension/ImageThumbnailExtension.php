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

namespace OpenDxp\Twig\Extension;

use OpenDxp\Model\Asset\Image;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
class ImageThumbnailExtension
{
    #[\Twig\Attribute\AsTwigFilter('opendxp_image_thumbnail', isSafe: ['html'])]
    #[\Twig\Attribute\AsTwigFunction('opendxp_image_thumbnail', isSafe: ['html'])]
    public function getImageThumbnail(Image $image, string $thumbnail, bool $deferred = true): Image\ThumbnailInterface
    {
        return $image->getThumbnail($thumbnail, $deferred);
    }

    #[\Twig\Attribute\AsTwigFilter('opendxp_image_thumbnail_html', isSafe: ['html'])]
    #[\Twig\Attribute\AsTwigFunction('opendxp_image_thumbnail_html', isSafe: ['html'])]
    public function getImageThumbnailHtml(
        Image $image,
        string $thumbnail,
        array $options = [],
        bool $deferred = true
    ): string {
        return $this->getImageThumbnail($image, $thumbnail, $deferred)->getHTML($options);
    }
}
