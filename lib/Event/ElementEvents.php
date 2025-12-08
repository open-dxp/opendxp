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

final class ElementEvents
{
    /**
     * Allows you to modify whether a permission on an element is granted or not
     *
     * Subject: \OpenDxp\Model\Element\AbstractElement
     * Arguments:
     *  - isAllowed | bool | the original "isAllowed" value as determined by opendxp. This can be modfied
     *  - permissionType | string | the permission that is checked
     *  - user | \OpenDxp\Model\User | user the permission is checked for
     *
     * @Event("OpenDxp\Event\Model\ElementEvent")
     */
    const string ELEMENT_PERMISSION_IS_ALLOWED = 'opendxp.element.permissions.isAllowed';
}
