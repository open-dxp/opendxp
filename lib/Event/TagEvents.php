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

final class TagEvents
{
    /**
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string PRE_ADD = 'opendxp.tag.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string POST_ADD = 'opendxp.tag.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string PRE_UPDATE = 'opendxp.tag.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string POST_UPDATE = 'opendxp.tag.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string PRE_DELETE = 'opendxp.tag.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string POST_DELETE = 'opendxp.tag.postDelete';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string PRE_ADD_TO_ELEMENT = 'opendxp.tag.preAddToElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string POST_ADD_TO_ELEMENT = 'opendxp.tag.postAddToElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string PRE_REMOVE_FROM_ELEMENT = 'opendxp.tag.preRemoveFromElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("OpenDxp\Event\Model\TagEvent")
     */
    const string POST_REMOVE_FROM_ELEMENT = 'opendxp.tag.postRemoveFromElement';
}
