<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\TestFoundation\Controller\DefaultController;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink;
use OpenDxp\Model\Document\Link;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\HardlinkFactory;
use OpenDxp\Test\Factory\LinkFactory;
use OpenDxp\Test\Factory\PageFactory;
use OpenDxp\Test\Factory\SnippetFactory;

it('writes a document and gives it an id', function () {

    $page = PageFactory::createOne();

    expect($page)
        ->toBeInstanceOf(Page::class)
        ->and($page->getId())
        ->toBeGreaterThan(0)
        ->and(Page::getById($page->getId()))
        ->toBeInstanceOf(Page::class);
});

it('takes the key the caller names', function () {
    expect(PageFactory::createOne(['key' => 'about-us'])->getKey())->toBe('about-us');
});

it('gives every document a key of its own when none is named', function () {

    $keys = array_map(static fn (Page $page) => $page->getKey(), PageFactory::createMany(3));

    expect(array_unique($keys))->toHaveCount(3);
});

it('hands back a document that was never written', function () {

    $page = PageFactory::new()->unsaved()->create();

    expect($page)
        ->toBeInstanceOf(Page::class)
        ->and($page->getId())
        ->toBeNull();
});

it('puts a document below another one', function () {

    $parent = PageFactory::createOne(['key' => 'en']);
    $child = PageFactory::new()->withParent($parent)->create(['key' => 'about-us']);

    expect($child->getParentId())
        ->toBe($parent->getId())
        ->and($child->getFullPath())
        ->toBe('/en/about-us');
});

it('marks the language a document belongs to', function () {

    $page = PageFactory::new()->withLocale('de_CH')->create();

    expect($page->getProperty('language'))->toBe('de_CH');
});

it('leaves a document unpublished when asked to', function () {
    expect(PageFactory::new()->unpublished()->create()->isPublished())
        ->toBeFalse()
        ->and(PageFactory::createOne()->isPublished())
        ->toBeTrue();
});

it('leaves the controller to the application unless a test names one', function () {

    $named = PageFactory::new()->withController(DefaultController::class, 'javascriptAction')->create();

    expect($named->getController())
        ->toBe(DefaultController::class . '::javascriptAction')
        ->and(PageFactory::createOne()->getController())
        ->toBe(DefaultController::class . '::defaultAction');
});

it('writes a snippet', function () {
    expect(SnippetFactory::createOne()->getId())->toBeGreaterThan(0);
});

it('points a link at another document', function () {

    $target = PageFactory::createOne();
    $link = LinkFactory::new()->withTarget($target)->create();

    expect($link)
        ->toBeInstanceOf(Link::class)
        ->and($link->getInternal())
        ->toBe($target->getId())
        ->and($link->getLinktype())
        ->toBe('internal');
});

it('mirrors a document with a hardlink', function () {

    $source = PageFactory::createOne();
    $hardlink = HardlinkFactory::new()->withSource($source)->withLocale('de')->create();

    expect($hardlink)
        ->toBeInstanceOf(Hardlink::class)
        ->and($hardlink->getSourceId())
        ->toBe($source->getId())
        ->and($hardlink->getChildrenFromSource())
        ->toBeTrue()
        ->and($hardlink->getProperty('language'))
        ->toBe('de');
});

it('links a document as the language variant of another', function () {

    $en = PageFactory::new()->withLocale('en')->create(['key' => 'en']);
    $de = PageFactory::new()->withLocale('de')->withTranslationOf($en)->create(['key' => 'de']);

    $variants = (new Document\Service())->getTranslations($en);

    expect($variants)
        ->toHaveKey('de')
        ->and($variants['de'])
        ->toBe($de->getId());
});

it('joins a third document to the same set of variants', function () {

    $en = PageFactory::new()->withLocale('en')->create(['key' => 'en-root']);
    $de = PageFactory::new()->withLocale('de')->withTranslationOf($en)->create(['key' => 'de-root']);
    $fr = PageFactory::new()->withLocale('fr')->withTranslationOf($de)->create(['key' => 'fr-root']);

    $variants = (new Document\Service())->getTranslations($en);
    ksort($variants);

    expect($variants)->toBe(['de' => $de->getId(), 'en' => $en->getId(), 'fr' => $fr->getId()]);
});
