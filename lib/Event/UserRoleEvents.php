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

final class UserRoleEvents
{
    /**
     * @Event("OpenDxp\Event\Model\UserRoleEvent")
     */
    const string PRE_ADD = 'opendxp.user.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\UserRoleEvent")
     */
    const string POST_ADD = 'opendxp.user.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\UserRoleEvent")
     */
    const string PRE_UPDATE = 'opendxp.user.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\UserRoleEvent")
     */
    const string POST_UPDATE = 'opendxp.user.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\UserRoleEvent")
     */
    const string PRE_DELETE = 'opendxp.user.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\UserRoleEvent")
     */
    const string POST_DELETE = 'opendxp.user.postDelete';
}
