<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Document\Editable\Areablock;
use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\DocumentSnippetFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\Tests\Application\Brick\GreetingBrick;
use Symfony\Component\DomCrawler\Crawler;

it('places bricks in an areablock in the order they are given', function () {
    $page = DocumentPageFactory::new()
        ->withBricks(
            'content',
            GreetingBrick::saying('Hello'),
            GreetingBrick::saying('Goodbye'),
        )
        ->create();

    $greetings = Browser::visit($page->getFullPath())
        ->crawler()
        ->filter('p.greeting')
        ->each(static fn (Crawler $greeting): string => trim($greeting->text()));

    expect($greetings)
        ->toBe([
            'Hello',
            'Goodbye',
        ]);
});

it('stores the bricks with the document', function () {
    $page = DocumentPageFactory::new()
        ->withBricks('content', GreetingBrick::saying('Hello'))
        ->create();

    $stored = Page::getById($page->getId(), ['force' => true]);

    expect($stored->getEditable('content'))
        ->toBeInstanceOf(Areablock::class)
        ->and($stored->getEditable('content')->getIndices())
        ->toBe([[
            'key' => '1',
            'type' => 'greeting',
            'hidden' => false,
        ]])
        ->and($stored->getEditable('content:1.text')->getData())
        ->toBe('Hello');
});

it('stores editables directly on the document', function () {
    $text = new Input();
    $text->setDataFromResource('Imprint');

    $page = DocumentPageFactory::new()
        ->withEditables(['headline' => $text])
        ->create();

    expect(Page::getById($page->getId(), ['force' => true])->getEditable('headline')->getData())
        ->toBe('Imprint');
});

it('gives every document it creates editables of its own', function () {
    $pages = DocumentPageFactory::new()
        ->withBricks('content', GreetingBrick::saying('Hello'))
        ->many(2)
        ->create();

    expect($pages[0]->getEditable('content:1.text'))
        ->not->toBe($pages[1]->getEditable('content:1.text'));
});

it('places bricks on a snippet as well', function () {
    $snippet = DocumentSnippetFactory::new()
        ->withBricks('content', GreetingBrick::saying('Hello'))
        ->create();

    expect($snippet->getEditable('content:1.text')->getData())
        ->toBe('Hello');
});
