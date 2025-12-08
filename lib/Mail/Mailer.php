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

namespace OpenDxp\Mail;

use OpenDxp\Mail;
use OpenDxp\Mail\Plugins\RedirectingPlugin;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

class Mailer implements MailerInterface
{
    public function __construct(protected MailerInterface $mailer, protected RedirectingPlugin $redirectPlugin)
    {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        if ($message instanceof Mail) {
            $this->redirectPlugin->beforeSendPerformed($message);
        }

        if ($message instanceof Message && !$message->getHeaders()->has('X-Transport')) {
            $message->getHeaders()->addTextHeader('X-Transport', 'main');
        }

        $this->mailer->send($message, $envelope);

        if ($message instanceof Mail) {
            $this->redirectPlugin->sendPerformed($message);
        }
    }
}
