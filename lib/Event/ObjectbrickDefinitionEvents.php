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

final class ObjectbrickDefinitionEvents
{
    /**
     * @Event("OpenDxp\Event\Model\DataObject\ObjectbrickDefinitionEvent")
     */
    const string PRE_ADD = 'opendxp.objectbrick.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ObjectbrickDefinitionEvent")
     */
    const string POST_ADD = 'opendxp.objectbrick.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ObjectbrickDefinitionEvent")
     */
    const string PRE_UPDATE = 'opendxp.objectbrick.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ObjectbrickDefinitionEvent")
     */
    const string POST_UPDATE = 'opendxp.objectbrick.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ObjectbrickDefinitionEvent")
     */
    const string PRE_DELETE = 'opendxp.objectbrick.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ObjectbrickDefinitionEvent")
     */
    const string POST_DELETE = 'opendxp.objectbrick.postDelete';
}
