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

const INJECTED = '<!-- INJECTED -->';

beforeEach(function () {
    $this->responseHelper = $this->createMock(ResponseHelper::class);
    $this->injector = new CodeInjector($this->responseHelper);
});

$page = <<<HTML
<html><head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar="">
    <!-- ORIG BODY -->
</body></html>
HTML;

$pageWithDiv = <<<HTML
<html><head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar="">
    <!-- ORIG BODY -->
    <div class="bar"><!-- ORIG DIV --></div>
</body></html>
HTML;

it('leaves a page alone that has nothing to inject into', function () {

    $page = '<html><body></body></html>';

    expect($this->injector->injectIntoHtml($page, INJECTED, CodeInjector::SELECTOR_HEAD, CodeInjector::POSITION_BEGINNING))
        ->toBe($page);
});

it('leaves a response alone that the response helper does not call html', function () {

    $this->responseHelper->method('isHtmlResponse')->willReturn(false);
    $content = '<html><head></head><body>foo</body></html>';
    $response = new Response($content);

    $this->injector->inject($response, INJECTED, CodeInjector::SELECTOR_BODY, CodeInjector::POSITION_BEGINNING);

    expect($response->getContent())->toBe($content);
});

it('injects into head or body', function (string $selector, string $position, string $source, string $expected) {
    expect($this->injector->injectIntoHtml($source, INJECTED, $selector, $position))->toBe($expected);
})->with([
    'at the start of the head' => [CodeInjector::SELECTOR_HEAD, CodeInjector::POSITION_BEGINNING, $page, <<<HTML
    <html><head><!-- INJECTED -->
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
    </body></html>
    HTML],
    'at the end of the head' => [CodeInjector::SELECTOR_HEAD, CodeInjector::POSITION_END, $page, <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    <!-- INJECTED --></head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
    </body></html>
    HTML],
    'in place of the head' => [CodeInjector::SELECTOR_HEAD, CodeInjector::REPLACE, $page, <<<HTML
    <html><head><!-- INJECTED --></head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
    </body></html>
    HTML],
    'at the start of the body' => [CodeInjector::SELECTOR_BODY, CodeInjector::POSITION_BEGINNING, $page, <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar=""><!-- INJECTED -->
        <!-- ORIG BODY -->
    </body></html>
    HTML],
    'at the end of the body' => [CodeInjector::SELECTOR_BODY, CodeInjector::POSITION_END, $page, <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
    <!-- INJECTED --></body></html>
    HTML],
    'in place of the body' => [CodeInjector::SELECTOR_BODY, CodeInjector::REPLACE, $page, <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar=""><!-- INJECTED --></body></html>
    HTML],
]);

it('injects into the element a selector picks out', function (string $selector, string $position, string $source, string $expected) {
    expect($this->injector->injectIntoHtml($source, INJECTED, $selector, $position))->toBe($expected);
})->with([
    'in place of its content' => ['body > div.bar', CodeInjector::REPLACE, $pageWithDiv, <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
        <div class="bar"><!-- INJECTED --></div>
    </body></html>
    HTML],
    'before its content' => ['body > div.bar', CodeInjector::POSITION_BEGINNING, $pageWithDiv, <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
        <div class="bar"><!-- INJECTED --><!-- ORIG DIV --></div>
    </body></html>
    HTML],
    'after its content' => ['body > div.bar', CodeInjector::POSITION_END, $pageWithDiv, <<<HTML
    <html><head>
        <!-- ORIG HEAD -->
    </head>
    <body class="foo" bar="">
        <!-- ORIG BODY -->
        <div class="bar"><!-- ORIG DIV --><!-- INJECTED --></div>
    </body></html>
    HTML],
    'nowhere, because nothing matches' => ['.non-existing', CodeInjector::POSITION_END, $pageWithDiv, $pageWithDiv],
]);

it('refuses a position it does not know', function (string $position) {
    $this->injector->injectIntoHtml('foo', 'bar', CodeInjector::SELECTOR_BODY, $position);
})->with(['foo', 'bar'])->throws(InvalidArgumentException::class);
