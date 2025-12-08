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

final class WorkflowEvents
{
    /**
     * Fired BEFORE a global action happens in the workflow. use this to hook into actions globally and define
     * your own logic. i.e. validation or checks on other system vars
     *
     * @Event("OpenDxp\Event\Workflow\GlobalActionEvent")
     */
    const string PRE_GLOBAL_ACTION = 'opendxp.workflow.preGlobalAction';

    /**
     * 	Fired AFTER a global action happens in the workflow. Use this to hook into actions globally and
     * define your own logic. i.e. trigger an email or maintenance job.
     *
     * @Event("OpenDxp\Event\Workflow\GlobalActionEvent")
     */
    const string POST_GLOBAL_ACTION = 'opendxp.workflow.postGlobalAction';
}
