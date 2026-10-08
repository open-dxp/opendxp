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

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Document\Editable\Areablock;
use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\DocumentSnippetFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\Tests\Application\Brick\BoxBrick;
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

    expect($greetings)->toBe([
        'Hello',
        'Goodbye',
    ]);
});

it('stores the bricks with the document', function () {
    $page = DocumentPageFactory::new()
        ->withBricks(
            'content',
            GreetingBrick::saying('Hello'),
        )
        ->create();

    $stored = reloaded($page);
    expect($stored->getEditable('content'))
        ->toBeInstanceOf(Areablock::class)
        ->getIndices()
        ->toBe([
            [
                'key' => '1',
                'type' => 'greeting',
                'hidden' => false,
            ],
        ])
        ->and($stored->getEditable('content:1.text'))
        ->getData()
        ->toBe('Hello');
});

it('places bricks in an areablock inside a brick', function () {
    $page = DocumentPageFactory::new()
        ->withBricks(
            'content',
            BoxBrick::containing(
                GreetingBrick::saying('Hello'),
                GreetingBrick::saying('Goodbye'),
            ),
        )
        ->create();

    $greetings = Browser::visit($page->getFullPath())
        ->crawler()
        ->filter('section.box p.greeting')
        ->each(static fn (Crawler $greeting): string => trim($greeting->text()));

    expect($greetings)->toBe([
        'Hello',
        'Goodbye',
    ]);
});

it('stores a brick inside a brick under the name OpenDXP gives it', function () {
    $page = DocumentPageFactory::new()
        ->withBricks(
            'content',
            BoxBrick::containing(GreetingBrick::saying('Hello')),
        )
        ->create();

    $stored = reloaded($page);

    expect($stored->getEditable('content:1.inside'))
        ->toBeInstanceOf(Areablock::class)
        ->and($stored->getEditable('content:1.inside:1.text'))
        ->getData()
        ->toBe('Hello');
});

it('stores editables directly on the document', function () {
    $headline = new Input();
    $headline->setDataFromResource('Imprint');

    $page = DocumentPageFactory::new()
        ->withEditables(['headline' => $headline])
        ->create();

    expect(reloaded($page)->getEditable('headline'))->getData()->toBe('Imprint');
});

it('gives every document it creates editables of its own', function () {
    $pages = DocumentPageFactory::new()
        ->withBricks(
            'content',
            GreetingBrick::saying('Hello'),
        )
        ->many(2)
        ->create();

    expect($pages[0]->getEditable('content:1.text'))->not->toBe($pages[1]->getEditable('content:1.text'));
});

it('places bricks on a snippet as well', function () {
    $snippet = DocumentSnippetFactory::new()
        ->withBricks(
            'content',
            GreetingBrick::saying('Hello'),
        )
        ->create();

    expect($snippet->getEditable('content:1.text'))->getData()->toBe('Hello');
});
