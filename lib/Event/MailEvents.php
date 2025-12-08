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

final class MailEvents
{
    /**
     * Arguments:
     *  - mailer | \OpenDxp\Mail\Mailer | contains the mailer object. Modify (or unset) this parameter if you want to implement a custom mail sending method
     *
     * @Event("OpenDxp\Event\Model\MailEvent")
     */
    const string PRE_SEND = 'opendxp.mail.preSend';

    const string PRE_LOG = 'opendxp.mail.preLog';
}
