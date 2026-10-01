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


namespace OpenDxp\Tests\Feature\Mail;

use OpenDxp\Mail;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\TextPart;

const FROM = 'jane@doe.com';
const TO = 'john@doe.com';
const SUBJECT = 'Test Subject';
const TEXT = 'This is a test mail.';

function headers(): Headers
{
    return (new Headers())
        ->addMailboxListHeader('From', [FROM])
        ->addMailboxListHeader('To', [TO]);
}

it('takes sender, recipient and subject from the headers it was built with', function () {

    $mail = new Mail(headers()->addTextHeader('Subject', SUBJECT), new TextPart(TEXT));

    expect($mail->getFrom()[0]->getAddress())
        ->toBe(FROM)
        ->and($mail->getTo()[0]->getAddress())
        ->toBe(TO)
        ->and($mail->getSubject())
        ->toBe(SUBJECT);
});

it('takes the same from an array of headers, body and subject', function () {

    $mail = new Mail(['headers' => headers(), 'body' => new TextPart(TEXT), 'subject' => SUBJECT]);

    expect($mail->getFrom()[0]->getAddress())
        ->toBe(FROM)
        ->and($mail->getTo()[0]->getAddress())
        ->toBe(TO)
        ->and($mail->getSubject())
        ->toBe(SUBJECT);
});

it('takes sender and reply address from the system settings when it was built with none', function () {

    $mail = new Mail();

    expect($mail->getFrom()[0]->getAddress())
        ->toBe('sender@tests.local')
        ->and($mail->getReplyTo()[0]->getAddress())
        ->toBe('return@tests.local');
});

it('keeps the address that was added to it', function (string $recipient, string $address) {

    $mail = new Mail();
    $mail->clearRecipients();

    $mail->{'add' . $recipient}($address, 'John Doe');

    expect($mail->{'get' . $recipient}()[0]->getAddress())->toBe($address);
})->with([
    'a recipient' => ['To', 'john@doe.com'],
    'a carbon copy' => ['Cc', 'john-cc@doe.com'],
    'a blind carbon copy' => ['Bcc', 'john-bcc@doe.com'],
    'a reply address' => ['ReplyTo', 'john-reply-to@doe.com'],
]);

it('holds no address at all once the recipients were cleared', function (string $recipient) {

    $mail = new Mail();
    $mail->addTo(TO)->addCc(TO)->addBcc(TO)->addReplyTo(TO);

    $mail->clearRecipients();

    expect($mail->{'get' . $recipient}())->toBeEmpty();
})->with([
    'the recipients' => ['To'],
    'the carbon copies' => ['Cc'],
    'the blind carbon copies' => ['Bcc'],
    'the reply addresses' => ['ReplyTo'],
]);

it('renders the parameters into the body', function (string $sets, string $renders, string $expected) {

    $mail = new Mail();
    $mail->{$sets}('Hi, {{ firstname }} {{ lastname }}.');
    $mail->setParams(['firstname' => 'John', 'lastname' => 'Doe']);

    expect($mail->{$renders}())->toContain($expected);
})->with([
    'the text body' => ['text', 'getBodyTextRendered', 'Hi, John Doe.'],
    'the html body' => ['html', 'getBodyHtmlRendered', 'Hi, John Doe.'],
]);
