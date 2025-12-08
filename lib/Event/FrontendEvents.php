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

namespace OpenDxp\Event;

final class FrontendEvents
{
    /**
     * Allows to rewrite the frontend path of an image thumbnail
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: OpenDxp\Model\Asset\Image\Thumbnail
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string ASSET_IMAGE_THUMBNAIL = 'opendxp.frontend.path.asset.image.thumbnail';

    /**
     * Allows to rewrite the frontend path of an video image thumbnail
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: OpenDxp\Model\Asset\Video\ImageThumbnail
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string ASSET_VIDEO_IMAGE_THUMBNAIL = 'opendxp.frontend.path.asset.video.image-thumbnail';

    /**
     * Allows to rewrite the frontend path of an video thumbnail (mp4)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: OpenDxp\Model\Asset\Video
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string ASSET_VIDEO_THUMBNAIL = 'opendxp.frontend.path.asset.video.thumbnail';

    /**
     * Allows to rewrite the frontend path of an video thumbnail (mp4)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	OpenDxp\Model\Asset\Document\ImageThumbnail
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string ASSET_DOCUMENT_IMAGE_THUMBNAIL = 'opendxp.frontend.path.asset.document.image-thumbnail';

    /**
     * Allows to rewrite the frontend path of an asset (no matter which type)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	OpenDxp\Model\Asset
     * Arguments:
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string ASSET_PATH = 'opendxp.frontend.path.asset';

    /**
     * Allows to rewrite the frontend path of a document (no matter which type)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	OpenDxp\Model\Document
     * Arguments:
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string DOCUMENT_PATH = 'opendxp.frontend.path.document';

    /**
     * Allows to rewrite the frontend path of a static route
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute
     * Arguments:
     *  - frontendPath | string | Web-path, relative
     *  - params | array
     *  - reset | bool
     *  - encode | bool
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string STATICROUTE_PATH = 'opendxp.frontend.path.staticroute';

    /**
     * Subject: 	\OpenDxp\Twig\Extension\Templating\HeadLink
     * Arguments:
     *  - item | stdClass
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string VIEW_HELPER_HEAD_LINK = 'opendxp.frontend.view.helper.head-link';

    /**
     * Subject: 	\OpenDxp\Twig\Extension\Templating\HeadScript
     * Arguments:
     *  - item | stdClass
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string VIEW_HELPER_HEAD_SCRIPT = 'opendxp.frontend.view.helper.head-script';
}
