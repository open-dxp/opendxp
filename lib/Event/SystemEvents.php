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

final class SystemEvents
{
    /**
     * This event is fired on shutdown (register_shutdown_function)
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string SHUTDOWN = 'opendxp.system.shutdown';

    /**
     * 	See Console / CLI | allow to register console commands (e.g. through plugins)
     *
     * @Event("OpenDxp\Event\System\ConsoleEvent")
     */
    const string CONSOLE_INIT = 'opendxp.system.console.init';

    /**
     * This event is fired on maintenance mode activation
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string MAINTENANCE_MODE_ACTIVATE = 'opendxp.system.maintenance_mode.activate';

    /**
     * This event is fired on maintenance mode deactivation
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string MAINTENANCE_MODE_DEACTIVATE = 'opendxp.system.maintenance_mode.deactivate';

    /**
     * This event is fired when maintenance mode is scheduled for the next login
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string MAINTENANCE_MODE_SCHEDULE_LOGIN = 'opendxp.system.maintenance_mode.schedule_login';

    /**
     * This event is fired when maintenance mode is unscheduled
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string MAINTENANCE_MODE_UNSCHEDULE_LOGIN = 'opendxp.system.maintenance_mode.unschedule_login';

    /**
     * This event is fired on Full-Page Cache clear
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string CACHE_CLEAR_FULLPAGE_CACHE = 'opendxp.system.cache.clearFullpageCache';

    /**
     * This event is fired on Cache clear
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string CACHE_CLEAR = 'opendxp.system.cache.clear';

    /**
     * This event is fired on Temporary Files clear
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string CACHE_CLEAR_TEMPORARY_FILES = 'opendxp.system.cache.clearTemporaryFiles';

    /**
     * This event is fired before OpenDxp adjusts element keys to generic rules
     *
     * @Event("\Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string SERVICE_PRE_GET_VALID_KEY = 'opendxp.system.service.preGetValidKey';

    /**
     * This event is fired before element service returns deep copy instance
     *
     * Arguments:
     *  - copier | deep copy instance
     *  - element | source element for deep copy
     *  - context | context info array i.e. 'source' => calling method, 'conversion' => 'marshal'/'unmarshal', 'defaultFilter' => true/false
     *
     * @Event("\Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string SERVICE_PRE_GET_DEEP_COPY = 'opendxp.system.service.preGetDeepCopy';

    /**
     * The SAVE_SYSTEM_SETTINGS event is triggered when the system settings are saved.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string SAVE_ACTION_SYSTEM_SETTINGS = 'opendxp.system.settings.saveAction';

    /**
     * The GET_SYSTEM_CONFIGURATION event is triggered when the system configuration is requested.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string GET_SYSTEM_CONFIGURATION = 'opendxp.system.configuration.get';
}
