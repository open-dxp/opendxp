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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Feature\Document;

use OpenDxp\Model\Document\Email;
use OpenDxp\Test\Factory\DocumentEmailFactory;

it('hands back every address and the subject it was saved with', function () {

    $email = DocumentEmailFactory::createOne([
        'subject' => 'a subject',
        'to' => 'john@doe.com',
        'cc' => 'john-cc@doe.com',
        'bcc' => 'john-bcc@doe.com',
        'from' => 'jane@doe.com',
        'replyTo' => 'jane-reply-to@doe.com',
    ]);

    $reloaded = Email::getById($email->getId(), ['force' => true]);

    expect($reloaded->getSubject())
        ->toBe('a subject')
        ->and($reloaded->getTo())
        ->toBe('john@doe.com')
        ->and($reloaded->getCc())
        ->toBe('john-cc@doe.com')
        ->and($reloaded->getBcc())
        ->toBe('john-bcc@doe.com')
        ->and($reloaded->getFrom())
        ->toBe('jane@doe.com')
        ->and($reloaded->getReplyTo())
        ->toBe('jane-reply-to@doe.com');
});
