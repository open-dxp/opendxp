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
use Closure;
use Exception;
use OpenDxp\Mail;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\TextPart;

function janeToJohn(): Headers
{
    return (new Headers())
        ->addMailboxListHeader('From', ['jane@doe.com'])
        ->addMailboxListHeader('To', ['john@doe.com']);
}

/**
 * @param array<string, mixed> $params
 */
function renderedMailBody(string $html, array $params): string
{
    $mail = new Mail();
    $mail->html($html);
    $mail->setParams($params);

    return $mail->getBodyHtmlRendered();
}

it('takes sender, recipient and subject from what it was built with', function (Mail $mail) {
    expect($mail->getFrom()[0]->getAddress())
        ->toBe('jane@doe.com')
        ->and($mail->getTo()[0]->getAddress())
        ->toBe('john@doe.com')
        ->and($mail->getSubject())
        ->toBe('Test Subject');
})->with([
    'headers and a body' => fn () => new Mail(
        janeToJohn()->addTextHeader('Subject', 'Test Subject'),
        new TextPart('This is a test mail.'),
    ),
    'an array' => fn () => new Mail([
        'headers' => janeToJohn(),
        'body' => new TextPart('This is a test mail.'),
        'subject' => 'Test Subject',
    ]),
]);

it('falls back to the configured sender and reply address', function () {
    $mail = new Mail();

    expect($mail->getFrom()[0]->getAddress())
        ->toBe('sender@tests.local')
        ->and($mail->getReplyTo()[0]->getAddress())
        ->toBe('return@tests.local');
});

it('keeps an address that was added to it', function (Closure $add, Closure $read) {
    $mail = new Mail();
    $mail->clearRecipients();

    $add($mail);

    expect($read($mail)[0]->getAddress())->toBe('john@doe.com');
})->with([
    'a recipient' => [
        fn (Mail $mail) => $mail->addTo('john@doe.com'),
        fn (Mail $mail) => $mail->getTo(),
    ],
    'a carbon copy' => [
        fn (Mail $mail) => $mail->addCc('john@doe.com'),
        fn (Mail $mail) => $mail->getCc(),
    ],
    'a blind carbon copy' => [
        fn (Mail $mail) => $mail->addBcc('john@doe.com'),
        fn (Mail $mail) => $mail->getBcc(),
    ],
    'a reply address' => [
        fn (Mail $mail) => $mail->addReplyTo('john@doe.com'),
        fn (Mail $mail) => $mail->getReplyTo(),
    ],
]);

it('holds no address once the recipients were cleared', function (Closure $read) {
    $mail = new Mail();
    $mail
        ->addTo('john@doe.com')
        ->addCc('john@doe.com')
        ->addBcc('john@doe.com')
        ->addReplyTo('john@doe.com');

    $mail->clearRecipients();

    expect($read($mail))->toBeEmpty();
})->with([
    'the recipients' => fn (Mail $mail) => $mail->getTo(),
    'the carbon copies' => fn (Mail $mail) => $mail->getCc(),
    'the blind carbon copies' => fn (Mail $mail) => $mail->getBcc(),
    'the reply addresses' => fn (Mail $mail) => $mail->getReplyTo(),
]);

it('renders the parameters into the body', function (Closure $write, Closure $render) {
    $mail = new Mail();
    $write($mail, 'Hi, {{ firstname }} {{ lastname }}.');
    $mail->setParams([
        'firstname' => 'John',
        'lastname' => 'Doe',
    ]);

    $body = $render($mail);

    expect($body)->toContain('Hi, John Doe.');
})->with([
    'the text body' => [
        fn (Mail $mail, string $body) => $mail->text($body),
        fn (Mail $mail) => $mail->getBodyTextRendered(),
    ],
    'the html body' => [
        fn (Mail $mail, string $body) => $mail->html($body),
        fn (Mail $mail) => $mail->getBodyHtmlRendered(),
    ],
]);

it('renders the subject as plain text without escaping', function () {
    $mail = new Mail();
    $mail->subject('Hi {{ name }}');
    $mail->setParams(['name' => '<Jo & Co>']);

    $subject = $mail->getSubjectRendered();

    expect($subject)->toBe('Hi <Jo & Co>');
});

it('escapes the parameters in the html body', function () {
    $body = renderedMailBody('Hi {{ name }}', ['name' => '<b>Jo</b>']);

    expect($body)->toContain('Hi &lt;b&gt;Jo&lt;/b&gt;');
});

it('lets a placeholder call an opendxp function', function () {
    $body = renderedMailBody('{{ opendxp_file_extension("report.pdf") }}', []);

    expect($body)->toContain('pdf');
});

it('refuses a filter the sandbox policy does not allow', function () {
    renderedMailBody('{{ "jo"|upper }}', []);
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

    expect($body)->toContain('Jane|Jane|42|2026');
});

it('reads a document in a placeholder', function () {
    $document = DocumentPageFactory::createOne(['title' => 'Welcome']);

    $body = renderedMailBody('{{ document.title }}', ['document' => $document]);

    expect($body)->toContain('Welcome');
});

it('renders a global', function () {
    $body = renderedMailBody('{{ editmode ? "edit" : "view" }}', []);

    expect($body)->toContain('view');
});

it('refuses the service container', function (string $html) {
    renderedMailBody($html, []);
})->with([
    'a parameter' => '{{ container.getParameter("kernel.environment") }}',
    'a service' => '{{ container.get("database_connection").fetchOne("SELECT 42") }}',
])->throws(Exception::class, 'Failed rendering the body');

it('refuses the request', function () {
    renderedMailBody('{{ app.request.server.get("APP_SECRET") }}', []);
})->throws(Exception::class, 'Failed rendering the body');

it('refuses the database behind an object', function (string $html) {
    renderedMailBody($html, ['object' => UnittestFactory::createOne()]);
})->with([
    'through the dao' => '{{ object.dao.db.fetchOne("SELECT 42") }}',
    'through a call the dao answers' => '{{ object.getDb().fetchOne("SELECT 42") }}',
])->throws(Exception::class, 'Failed rendering the body');

it('refuses to change an object', function (string $html) {
    renderedMailBody($html, ['object' => UnittestFactory::createOne()]);
})->with([
    'through a setter' => '{{ object.setInput("John") }}',
    'through save' => '{{ object.save() }}',
])->throws(Exception::class, 'Failed rendering the body');

it('refuses to delete an object', function () {
    $object = UnittestFactory::createOne();

    $deleting = fn () => renderedMailBody('{{ object.delete() }}', ['object' => $object]);

    expect($deleting)
        ->toThrow(Exception::class, 'Failed rendering the body')
        ->and(reloaded($object))
        ->not->toBeNull();
});

it('refuses the dump function', function () {
    renderedMailBody('{{ opendxp_dump(object) }}', ['object' => UnittestFactory::createOne()]);
})->throws(Exception::class, 'Function "opendxp_dump" is not allowed');

it('calls a method the configuration allows', function () {
    $body = renderedMailBody('{{ app.environment }}', []);

    expect($body)->toContain('test');
});
