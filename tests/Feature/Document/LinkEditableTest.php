<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Document;

use OpenDxp\Model\Document\Editable\Link;
use OpenDxp\Test\Factory\DocumentPageFactory;

it('renders only the fields of the link dialog as attributes', function () {
    $page = DocumentPageFactory::createOne();
    $link = (new Link())->setDataFromEditmode([
        'internalType' => 'document',
        'linktype' => 'internal',
        'path' => $page->getFullPath(),
        'text' => 'Page',
        'title' => 'Our page',
        'onclick' => 'track()',
    ]);

    $html = $link->frontend();

    expect($html)->toBe(sprintf('<a href="%s" title="Our page">Page</a>', $page->getFullPath()));
});

it('escapes a quote in the path of a link', function () {
    $link = (new Link())->setDataFromEditmode([
        'linktype' => 'direct',
        'path' => 'https://example.com/" data-x="1',
        'text' => 'Example',
    ]);

    $html = $link->frontend();

    expect($html)->toBe('<a href="https://example.com/&quot; data-x=&quot;1" >Example</a>');
});

it('renders no link for a url that runs a script', function (string $path) {
    $link = (new Link())->setDataFromEditmode([
        'linktype' => 'direct',
        'path' => $path,
        'text' => 'Click',
    ]);

    $html = $link->frontend();

    expect($html)->toBe('');
})->with([
    'javascript' => 'javascript:alert(1)',
    'vbscript' => 'vbscript:msgbox(1)',
    'an html data url' => 'data:text/html,<script>alert(1)</script>',
    'an svg data url' => 'data:image/svg+xml,<svg onload="alert(1)"/>',
    'a scheme with a tab' => "java\tscript:alert(1)",
    'a scheme with a character reference' => 'javascript&#58;alert(1)',
]);

it('renders a link to a data url of an image', function () {
    $link = (new Link())->setDataFromEditmode([
        'linktype' => 'direct',
        'path' => 'data:image/png;base64,iVBORw0KGgo=',
        'text' => 'Image',
    ]);

    $html = $link->frontend();

    expect($html)->toBe('<a href="data:image/png;base64,iVBORw0KGgo=" >Image</a>');
});
