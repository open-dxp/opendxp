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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\Exception;

use Exception;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Attribute\WithLogLevel;

/**
 * Signals that a configuration can't be saved because its storage isn't writeable, such as a thumbnail or a static route.
 * The admin's AdminExceptionListener turns it into a 422 response that shows the message to the user.
 * Symfony's ErrorListener logs every exception, this one at warning level, since the environment keeps that configuration read-only.
 */
#[WithLogLevel(LogLevel::WARNING)]
class ConfigWriteException extends Exception
{
}
