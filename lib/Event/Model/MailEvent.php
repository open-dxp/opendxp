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

namespace OpenDxp\Event\Model;

use OpenDxp\Event\Traits\ArgumentsAwareTrait;
use OpenDxp\Mail;
use Symfony\Contracts\EventDispatcher\Event;

class MailEvent extends Event
{
    use ArgumentsAwareTrait;

    public function __construct(protected Mail $mail, array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function getMail(): Mail
    {
        return $this->mail;
    }

    public function setMail(Mail $mail): static
    {
        $this->mail = $mail;

        return $this;
    }
}
