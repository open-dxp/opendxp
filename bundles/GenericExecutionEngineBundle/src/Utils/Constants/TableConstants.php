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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants;

final class TableConstants
{
    public const string USER_PERMISSION_DEF_TABLE = 'users_permission_definitions';

    public const string JOB_RUN_TABLE = 'generic_execution_engine_job_run';

    public const string ERROR_LOG_TABLE = 'generic_execution_engine_error_log';
}
