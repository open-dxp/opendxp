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

final class DataObjectQuantityValueEvents
{
    /**
     * @Event("OpenDxp\Event\Model\DataObject\QuantityValueUnitEvent")
     */
    const string UNIT_PRE_ADD = 'opendxp.dataobject.quantityvalue.unit.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\QuantityValueUnitEvent")
     */
    const string UNIT_POST_ADD = 'opendxp.dataobject.quantityvalue.unit.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\QuantityValueUnitEvent")
     */
    const string UNIT_PRE_UPDATE = 'opendxp.dataobject.quantityvalue.unit.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\QuantityValueUnitEvent")
     */
    const string UNIT_POST_UPDATE = 'opendxp.dataobject.quantityvalue.unit.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\QuantityValueUnitEvent")
     */
    const string UNIT_PRE_DELETE = 'opendxp.dataobject.quantityvalue.unit.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\QuantityValueUnitEvent")
     */
    const string UNIT_POST_DELETE = 'opendxp.dataobject.quantityvalue.unit.postDelete';
}
