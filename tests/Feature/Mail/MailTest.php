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

use Carbon\Carbon;
use Exception;
use OpenDxp\Mail;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;
use Symfony\Component\Mime\Part\TextPart;

const FROM = 'jane@doe.com';
const TO = 'john@doe.com';
const SUBJECT = 'Test Subject';
const TEXT = 'This is a test mail.';

it('takes sender, recipient and subject from the headers it was built with', function () {

    $mail = new Mail(
        mailHeaders(FROM, TO)->addTextHeader('Subject', SUBJECT),
        new TextPart(TEXT),
    );

    expect($mail->getFrom()[0]->getAddress())
        ->toBe(FROM)
        ->and($mail->getTo()[0]->getAddress())
        ->toBe(TO)
        ->and($mail->getSubject())
        ->toBe(SUBJECT);
});

it('takes the same from an array of headers, body and subject', function () {

    $mail = new Mail([
        'headers' => mailHeaders(FROM, TO),
        'body' => new TextPart(TEXT),
        'subject' => SUBJECT,
    ]);

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

    expect($mail->{'get' . $recipient}()[0]->getAddress())
        ->toBe($address);
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

    expect($mail->{'get' . $recipient}())
        ->toBeEmpty();
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

    expect($mail->{$renders}())
        ->toContain($expected);
})->with([
    'the text body' => ['text', 'getBodyTextRendered', 'Hi, John Doe.'],
    'the html body' => ['html', 'getBodyHtmlRendered', 'Hi, John Doe.'],
]);

it('renders the subject as plain text, without escaping', function () {
    $mail = new Mail();
    $mail->subject('Hi {{ name }}');
    $mail->setParams(['name' => '<Jo & Co>']);

    expect($mail->getSubjectRendered())
        ->toBe('Hi <Jo & Co>');
});

it('escapes the parameters in the html body', function () {
    $mail = new Mail();
    $mail->html('Hi {{ name }}');
    $mail->setParams(['name' => '<b>Jo</b>']);

    expect($mail->getBodyHtmlRendered())
        ->toContain('Hi &lt;b&gt;Jo&lt;/b&gt;');
});

it('still lets a placeholder call an opendxp function', function () {
    $mail = new Mail();
    $mail->html('{{ opendxp_file_extension("report.pdf") }}');

    expect($mail->getBodyHtmlRendered())
        ->toContain('pdf');
});

it('refuses a filter the sandbox policy does not allow', function () {
    $mail = new Mail();
    $mail->html('{{ "jo"|upper }}');

    $mail->getBodyHtmlRendered();
})->throws(Exception::class, 'Failed rendering the body');

it('reads an object in a placeholder', function () {
    $object = UnittestFactory::createOne([
        'input' => 'Jane',
        'number' => 42,
        'date' => Carbon::create(2026, 10, 5),
    ]);

    $body = renderedMailBody(
        '{{ object.input }}|{{ object.getInput() }}|{{ object.number }}|{{ object.date.format("Y") }}',
        ['object' => $object],
    );

    expect($body)
        ->toContain('Jane|Jane|42|2026');
});

it('reads a document in a placeholder', function () {
    $document = DocumentPageFactory::createOne(['title' => 'Welcome']);

    expect(renderedMailBody('{{ document.title }}', ['document' => $document]))
        ->toContain('Welcome');
});

it('renders a global', function () {
    expect(renderedMailBody('{{ editmode ? "edit" : "view" }}'))
        ->toContain('view');
});

it('refuses the service container', function (string $html) {
    renderedMailBody($html);
})->with([
    'a parameter' => ['{{ container.getParameter("kernel.environment") }}'],
    'a service' => ['{{ container.get("database_connection").fetchOne("SELECT 42") }}'],
])->throws(Exception::class, 'Failed rendering the body');

it('refuses the request', function () {
    renderedMailBody('{{ app.request.server.get("APP_SECRET") }}');
})->throws(Exception::class, 'Failed rendering the body');

it('refuses the database behind an object', function (string $html) {
    renderedMailBody($html, ['object' => UnittestFactory::createOne()]);
})->with([
    'through the dao' => ['{{ object.dao.db.fetchOne("SELECT 42") }}'],
    'through a call the dao answers' => ['{{ object.getDb().fetchOne("SELECT 42") }}'],
])->throws(Exception::class, 'Failed rendering the body');

it('refuses to change an object', function (string $html) {
    renderedMailBody($html, ['object' => UnittestFactory::createOne()]);
})->with([
    'a setter' => ['{{ object.setInput("John") }}'],
    'save' => ['{{ object.save() }}'],
])->throws(Exception::class, 'Failed rendering the body');

it('refuses to delete an object', function () {
    $object = UnittestFactory::createOne();

    expect(fn () => renderedMailBody('{{ object.delete() }}', ['object' => $object]))
        ->toThrow(Exception::class, 'Failed rendering the body')
        ->and(Unittest::getById($object->getId(), ['force' => true]))
        ->not->toBeNull();
});

it('refuses the dump function', function () {
    renderedMailBody('{{ opendxp_dump(object) }}', ['object' => UnittestFactory::createOne()]);
})->throws(Exception::class, 'Function "opendxp_dump" is not allowed');

it('calls a method the configuration allows', function () {
    expect(renderedMailBody('{{ app.environment }}'))
        ->toContain('test');
});
