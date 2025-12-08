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

final class DataObjectClassDefinitionEvents
{
    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassDefinitionEvent")
     */
    const string PRE_ADD = 'opendxp.class.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassDefinitionEvent")
     */
    const string POST_ADD = 'opendxp.class.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassDefinitionEvent")
     */
    const string PRE_UPDATE = 'opendxp.class.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassDefinitionEvent")
     */
    const string POST_UPDATE = 'opendxp.class.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassDefinitionEvent")
     */
    const string PRE_DELETE = 'opendxp.class.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassDefinitionEvent")
     */
    const string POST_DELETE = 'opendxp.class.postDelete';
}
