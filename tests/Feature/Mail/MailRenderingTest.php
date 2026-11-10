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
use OpenDxp\Test\Factory\DocumentEmailFactory;
use OpenDxp\Tests\Application\Brick\GreetingBrick;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

function greetingMail(string $firstname): Mail
{
    $document = DocumentEmailFactory::new()
        ->withBricks('content', GreetingBrick::saying('Welcome, {{ firstname }}.'))
        ->create();

    $mail = new Mail();
    $mail->addTo('john@doe.com');
    $mail->setDocument($document);
    $mail->setParams(['firstname' => $firstname]);

    return $mail;
}

function sentGreeting(string $firstname): Mail
{
    $mail = new Mail();
    $mail->addTo('john@doe.com');
    $mail->subject('Hi {{ firstname }}');
    $mail->html('<p>Hi {{ firstname }}</p>');
    $mail->setParams(['firstname' => $firstname]);

    return $mail->send(new Mailer(Transport::fromDsn('null://null')));
}

it('replaces a placeholder in the content of the document', function () {
    $body = greetingMail('Jane')->getBodyHtmlRendered();

    expect($body)->toContain('Welcome, Jane.');
});

it('does not run a parameter as a template in the text part of a sent document', function () {
    $mail = greetingMail('{{ 7*191 }}')->send(new Mailer(Transport::fromDsn('null://null')));

    expect($mail->getTextBody())
        ->not->toContain('1337')
        ->toContain('Welcome, {{ 7');
});

it('does not run a parameter as a template in the text part of a sent mail', function () {
    expect(sentGreeting('{{ 7*191 }}')->getTextBody())
        ->not->toContain('1337')
        ->toContain('Hi {{ 7');
});

it('does not run a parameter as a template in the logged subject', function () {
    expect(sentGreeting('{{ 7*191 }}')->getLastLogEntry()->getSubject())->toBe('Hi {{ 7*191 }}');
});

it('does not run a parameter as a template when the mail is sent again', function () {
    $mail = sentGreeting('{{ 7*191 }}');

    $mail->send(new Mailer(Transport::fromDsn('null://null')));

    expect($mail->getHtmlBody())->toContain('Hi {{ 7*191 }}');
});
