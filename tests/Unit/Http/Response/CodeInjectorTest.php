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

namespace OpenDxp\Tests\Unit\Http\Response;

use InvalidArgumentException;
use OpenDxp\Http\Response\CodeInjector;
use OpenDxp\Http\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;

function pageMarkup(): string
{
    return <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
    </body></html>
    HTML;
}

function pageMarkupWithDiv(): string
{
    return <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
        <div class="bar"><!-- ORIG DIV --></div>
    </body></html>
    HTML;
}

beforeEach(function () {
    $this->responseHelper = $this->createMock(ResponseHelper::class);
    $this->injector = new CodeInjector($this->responseHelper);
    $this->code = '<!-- INJECTED -->';
});

it('leaves a page alone that has no head to inject into', function () {
    $page = '<html><body></body></html>';

    $injected = $this->injector->injectIntoHtml(
        $page,
        $this->code,
        CodeInjector::SELECTOR_HEAD,
        CodeInjector::POSITION_BEGINNING,
    );

    expect($injected)->toBe($page);
});

it('leaves a response alone that is not HTML', function () {
    $this->responseHelper
        ->method('isHtmlResponse')
        ->willReturn(false);
    $content = '<html><head></head><body>foo</body></html>';
    $response = new Response($content);

    $this->injector->inject($response, $this->code, CodeInjector::SELECTOR_BODY, CodeInjector::POSITION_BEGINNING);

    expect($response->getContent())->toBe($content);
});

it('injects into the head or the body', function (string $selector, string $position, string $expected) {
    $page = pageMarkup();

    $injected = $this->injector->injectIntoHtml($page, $this->code, $selector, $position);

    expect($injected)->toBe($expected);
})->with([
    'at the start of the head' => [
        CodeInjector::SELECTOR_HEAD,
        CodeInjector::POSITION_BEGINNING,
        <<<HTML
        <html><head><!-- INJECTED -->
            <!-- ORIG HEAD -->
        </head>
        <body class="foo" bar="">
            <!-- ORIG BODY -->
        </body></html>
        HTML,
    ],
    'at the end of the head' => [
        CodeInjector::SELECTOR_HEAD,
        CodeInjector::POSITION_END,
        <<<HTML
        <html><head>
            <!-- ORIG HEAD -->
        <!-- INJECTED --></head>
        <body class="foo" bar="">
            <!-- ORIG BODY -->
        </body></html>
        HTML,
    ],
    'in place of the head' => [
        CodeInjector::SELECTOR_HEAD,
        CodeInjector::REPLACE,
        <<<HTML
        <html><head><!-- INJECTED --></head>
        <body class="foo" bar="">
            <!-- ORIG BODY -->
        </body></html>
        HTML,
    ],
    'at the start of the body' => [
        CodeInjector::SELECTOR_BODY,
        CodeInjector::POSITION_BEGINNING,
        <<<HTML
        <html><head>
            <!-- ORIG HEAD -->
        </head>
        <body class="foo" bar=""><!-- INJECTED -->
            <!-- ORIG BODY -->
        </body></html>
        HTML,
    ],
    'at the end of the body' => [
        CodeInjector::SELECTOR_BODY,
        CodeInjector::POSITION_END,
        <<<HTML
        <html><head>
            <!-- ORIG HEAD -->
        </head>
        <body class="foo" bar="">
            <!-- ORIG BODY -->
        <!-- INJECTED --></body></html>
        HTML,
    ],
    'in place of the body' => [
        CodeInjector::SELECTOR_BODY,
        CodeInjector::REPLACE,
        <<<HTML
        <html><head>
            <!-- ORIG HEAD -->
        </head>
        <body class="foo" bar=""><!-- INJECTED --></body></html>
        HTML,
    ],
]);

it('injects into the element a selector picks out', function (string $position, string $expected) {
    $page = pageMarkupWithDiv();

    $injected = $this->injector->injectIntoHtml($page, $this->code, 'body > div.bar', $position);

    expect($injected)->toBe($expected);
})->with([
    'in place of its content' => [
        CodeInjector::REPLACE,
        <<<HTML
        <html><head>
            <!-- ORIG HEAD -->
        </head>
        <body class="foo" bar="">
            <!-- ORIG BODY -->
            <div class="bar"><!-- INJECTED --></div>
        </body></html>
        HTML,
    ],
    'before its content' => [
        CodeInjector::POSITION_BEGINNING,
        <<<HTML
        <html><head>
            <!-- ORIG HEAD -->
        </head>
        <body class="foo" bar="">
            <!-- ORIG BODY -->
            <div class="bar"><!-- INJECTED --><!-- ORIG DIV --></div>
        </body></html>
        HTML,
    ],
    'after its content' => [
        CodeInjector::POSITION_END,
        <<<HTML
        <html><head>
            <!-- ORIG HEAD -->
        </head>
        <body class="foo" bar="">
            <!-- ORIG BODY -->
            <div class="bar"><!-- ORIG DIV --><!-- INJECTED --></div>
        </body></html>
        HTML,
    ],
]);

it('leaves a page alone when the selector matches nothing', function () {
    $page = pageMarkupWithDiv();

    $injected = $this->injector->injectIntoHtml($page, $this->code, '.non-existing', CodeInjector::POSITION_END);

    expect($injected)->toBe($page);
});

it('refuses a position it does not know', function () {
    $this->injector->injectIntoHtml('foo', $this->code, CodeInjector::SELECTOR_BODY, 'sideways');
})->throws(InvalidArgumentException::class, 'Invalid position. Supported positions are: beginning, end, replace');
