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

final class WebsiteSettingEvents
{
    /**
     * @Event("OpenDxp\Event\Model\WebsiteSettingEvent")
     */
    public const string PRE_ADD = 'opendxp.websiteSetting.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\WebsiteSettingEvent")
     */
    public const string POST_ADD = 'opendxp.websiteSetting.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\WebsiteSettingEvent")
     */
    public const string PRE_UPDATE = 'opendxp.websiteSetting.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\WebsiteSettingEvent")
     */
    public const string POST_UPDATE = 'opendxp.websiteSetting.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\WebsiteSettingEvent")
     */
    public const string PRE_DELETE = 'opendxp.websiteSetting.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\WebsiteSettingEvent")
     */
    public const string POST_DELETE = 'opendxp.websiteSetting.postDelete';
}
