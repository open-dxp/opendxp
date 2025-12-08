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

final class AssetEvents
{
    /**
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string PRE_ADD = 'opendxp.asset.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_ADD = 'opendxp.asset.postAdd';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_ADD_FAILURE = 'opendxp.asset.postAddFailure';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string PRE_UPDATE = 'opendxp.asset.preUpdate';

    /**
     * Arguments:
     *  - metadata
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string PRE_GET_METADATA = 'opendxp.asset.preGetMetadata';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - oldPath | the old full path in case the path has changed
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_UPDATE = 'opendxp.asset.postUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_UPDATE_FAILURE = 'opendxp.asset.postUpdateFailure';

    /**
     * @Event("OpenDxp\Bundle\AdminBundle\Event\Model\AssetDeleteInfoEvent")
     */
    const string DELETE_INFO = 'opendxp.asset.deleteInfo';

    /**
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string PRE_DELETE = 'opendxp.asset.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_DELETE = 'opendxp.asset.postDelete';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_DELETE_FAILURE = 'opendxp.asset.postDeleteFailure';

    /**
     * Arguments:
     *  - params | array | contains the values that were passed to getById() as the second parameter
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_LOAD = 'opendxp.asset.postLoad';

    /**
     * Arguments:
     *  - target_element | OpenDxp\Model\Asset | contains the target asset used in copying process
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string PRE_COPY = 'opendxp.asset.preCopy';

    /**
     * Arguments:
     *  - base_element | OpenDxp\Model\Asset | contains the base asset used in copying process
     *
     * @Event("OpenDxp\Event\Model\AssetEvent")
     */
    const string POST_COPY = 'opendxp.asset.postCopy';

    /**
     * Fires after the thumbnail was created
     *
     * Arguments:
     *  - deferred | bool | Whether the thumbnail should be generated on demand or not
     *  - generated | bool | Whether a new thumbnail file was actually generated or not (from cache)
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string IMAGE_THUMBNAIL = 'opendxp.asset.image.thumbnail';

    /**
     * Fires after the image thumbnail was created
     *
     * Arguments:
     *  - deferred | bool | Whether the thumbnail should be generated on demand or not
     *  - generated | bool | Whether a new thumbnail file was actually generated or not (from cache)
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string VIDEO_IMAGE_THUMBNAIL = 'opendxp.asset.video.image-thumbnail';

    /**
     * Fires after the image thumbnail was created
     *
     * Arguments:
     *  - deferred | bool | Whether the thumbnail should be generated on demand or not
     *  - generated | bool | Whether a new thumbnail file was actually generated or not (from cache)
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string DOCUMENT_IMAGE_THUMBNAIL = 'opendxp.asset.document.image-thumbnail';

    /**
     * Fires before an asset upload created
     *
     * @Event("OpenDxp\Event\Model\Asset\ResolveUploadTargetEvent")
     */
    const string RESOLVE_UPLOAD_TARGET = 'opendxp.asset.resolve-upload-target';
}
