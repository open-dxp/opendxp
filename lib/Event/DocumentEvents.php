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

final class DocumentEvents
{
    /**
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string PRE_ADD = 'opendxp.document.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_ADD = 'opendxp.document.postAdd';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_ADD_FAILURE = 'opendxp.document.postAddFailure';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string PRE_UPDATE = 'opendxp.document.preUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - oldPath | the old full path in case the path has changed
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_UPDATE = 'opendxp.document.postUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_UPDATE_FAILURE = 'opendxp.document.postUpdateFailure';

    /**
     * @Event("OpenDxp\Event\Model\DocumentDeleteInfoEvent")
     */
    const string DELETE_INFO = 'opendxp.document.deleteInfo';

    /**
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string PRE_DELETE = 'opendxp.document.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_DELETE = 'opendxp.document.postDelete';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_DELETE_FAILURE = 'opendxp.document.postDeleteFailure';

    /**
     * Arguments:
     *  - params | array | contains the values that were passed to getById() as the second parameter
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_LOAD = 'opendxp.document.postLoad';

    /**
     * Arguments:
     *  - target_element | OpenDxp\Model\Document | contains the target document used in copying process
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string PRE_COPY = 'opendxp.document.preCopy';

    /**
     * Arguments:
     *  - base_element | OpenDxp\Model\Document | contains the base document used in copying process
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_COPY = 'opendxp.document.postCopy';

    /**
     * The EDITABLE_NAME event is triggered when a document editable name is built.
     *
     * @Event("OpenDxp\Event\Model\Document\EditableNameEvent")
     *
     */
    const string EDITABLE_NAME = 'opendxp.document.editable.name';

    /**
     * The RENDERER_PRE_RENDER event is triggered before the DocumentRenderer renders a document
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string RENDERER_PRE_RENDER = 'opendxp.document.renderer.pre_render';

    /**
     * The RENDERER_POST_RENDER event is triggered after the DocumentRenderer rendered a document
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string RENDERER_POST_RENDER = 'opendxp.document.renderer.post_render';

    /**
     * The INCLUDERENDERER_PRE_RENDER event is triggered before the IncludeRenderer renders an include
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string INCLUDERENDERER_PRE_RENDER = 'opendxp.document.IncludeRenderer.pre_render';

    /**
     * Arguments:
     *  - element | \OpenDxp\Mail | the opendxp mail instance
     *  - requestParams | contains the request parameters
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string EDITABLE_RENDERLET_PRE_RENDER = 'opendxp.document.editable.renderlet.pre_render';

    /**
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string PAGE_POST_SAVE_ACTION = 'opendxp.document.page.post_save_action';

    /**
     *
     * @Event("OpenDxp\Event\Model\DocumentEvent")
     */
    const string POST_MOVE_ACTION = 'opendxp.document.post_move_action';
}
