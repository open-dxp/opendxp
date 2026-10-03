<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\TestFoundation\Controller\DefaultController;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink;
use OpenDxp\Model\Document\Link;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentHardlinkFactory;
use OpenDxp\Test\Factory\DocumentLinkFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\DocumentSnippetFactory;

it('writes a document and gives it an id', function () {

    $page = DocumentPageFactory::createOne();

    expect($page)
        ->toBeInstanceOf(Page::class)
        ->and($page->getId())
        ->toBeGreaterThan(0)
        ->and(Page::getById($page->getId()))
        ->toBeInstanceOf(Page::class);
});

it('takes the key the caller names', function () {
    expect(DocumentPageFactory::createOne(['key' => 'about-us'])->getKey())->toBe('about-us');
});

it('gives every document a key of its own when none is named', function () {

    $keys = array_map(static fn (Page $page) => $page->getKey(), DocumentPageFactory::createMany(3));

    expect(array_unique($keys))->toHaveCount(3);
});

it('hands back a document that was never written', function () {

    $page = DocumentPageFactory::new()->unsaved()->create();

    expect($page)
        ->toBeInstanceOf(Page::class)
        ->and($page->getId())
        ->toBeNull();
});

it('puts a document below another one', function () {

    $parent = DocumentPageFactory::createOne(['key' => 'en']);
    $child = DocumentPageFactory::new()->withParent($parent)->create(['key' => 'about-us']);

    expect($child->getParentId())
        ->toBe($parent->getId())
        ->and($child->getFullPath())
        ->toBe('/en/about-us');
});

it('marks the language a document belongs to', function () {

    $page = DocumentPageFactory::new()->withLocale('de_CH')->create();

    expect($page->getProperty('language'))->toBe('de_CH');
});

it('leaves a document unpublished when asked to', function () {
    expect(DocumentPageFactory::new()->unpublished()->create()->isPublished())
        ->toBeFalse()
        ->and(DocumentPageFactory::createOne()->isPublished())
        ->toBeTrue();
});

it('leaves the controller to the application unless a test names one', function () {

    $named = DocumentPageFactory::new()->withController(DefaultController::class, 'javascriptAction')->create();

    expect($named->getController())
        ->toBe(DefaultController::class . '::javascriptAction')
        ->and(DocumentPageFactory::createOne()->getController())
        ->toBe(DefaultController::class . '::defaultAction');
});

it('writes a snippet', function () {
    expect(DocumentSnippetFactory::createOne()->getId())->toBeGreaterThan(0);
});

it('points a link at another document', function () {

    $target = DocumentPageFactory::createOne();
    $link = DocumentLinkFactory::new()->withTarget($target)->create();

    expect($link)
        ->toBeInstanceOf(Link::class)
        ->and($link->getInternal())
        ->toBe($target->getId())
        ->and($link->getLinktype())
        ->toBe('internal');
});

it('mirrors a document with a hardlink', function () {

    $source = DocumentPageFactory::createOne();
    $hardlink = DocumentHardlinkFactory::new()->withSource($source)->withLocale('de')->create();

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

    $en = DocumentPageFactory::new()->withLocale('en')->create(['key' => 'en']);
    $de = DocumentPageFactory::new()->withLocale('de')->withTranslationOf($en)->create(['key' => 'de']);

    $variants = (new Document\Service())->getTranslations($en);

    expect($variants)
        ->toHaveKey('de')
        ->and($variants['de'])
        ->toBe($de->getId());
});

it('joins a third document to the same set of variants', function () {

    $en = DocumentPageFactory::new()->withLocale('en')->create(['key' => 'en-root']);
    $de = DocumentPageFactory::new()->withLocale('de')->withTranslationOf($en)->create(['key' => 'de-root']);
    $fr = DocumentPageFactory::new()->withLocale('fr')->withTranslationOf($de)->create(['key' => 'fr-root']);

    $variants = (new Document\Service())->getTranslations($en);
    ksort($variants);

    expect($variants)->toBe(['de' => $de->getId(), 'en' => $en->getId(), 'fr' => $fr->getId()]);
});
