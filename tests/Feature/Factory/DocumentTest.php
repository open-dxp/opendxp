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

use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink;
use OpenDxp\Model\Document\Link;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentHardlinkFactory;
use OpenDxp\Test\Factory\DocumentLinkFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\DocumentSnippetFactory;
use OpenDxp\TestFoundation\Controller\DefaultController;

it('writes a document', function () {
    $page = DocumentPageFactory::createOne();

    expect(reloaded($page))->toBeInstanceOf(Page::class);
});

it('takes the key a test names', function () {
    $page = DocumentPageFactory::createOne(['key' => 'about-us']);

    expect($page->getKey())->toBe('about-us');
});

it('gives every document a key of its own when none is named', function () {
    $pages = DocumentPageFactory::createMany(3);

    $keys = array_map(
        static fn (Page $page): string => $page->getKey(),
        $pages,
    );

    expect(array_unique($keys))->toHaveCount(3);
});

it('returns a document that was never written', function () {
    $page = DocumentPageFactory::new()
        ->unsaved()
        ->create();

    expect($page)
        ->toBeInstanceOf(Page::class)
        ->getId()
        ->toBeNull();
});

it('puts a document below its parent', function () {
    $parent = DocumentPageFactory::createOne(['key' => 'en']);

    $child = DocumentPageFactory::new()
        ->withParent($parent)
        ->create(['key' => 'about-us']);

    expect($child->getFullPath())->toBe('/en/about-us');
});

it('marks the language a document belongs to', function () {
    $page = DocumentPageFactory::new()
        ->withLocale('de_CH')
        ->create();

    expect($page->getProperty('language'))->toBe('de_CH');
});

it('publishes a document', function () {
    $page = DocumentPageFactory::createOne();

    expect($page->isPublished())->toBeTrue();
});

it('leaves a document unpublished on request', function () {
    $page = DocumentPageFactory::new()
        ->unpublished()
        ->create();

    expect($page->isPublished())->toBeFalse();
});

it('uses the default controller unless a test names one', function () {
    $page = DocumentPageFactory::createOne();

    expect($page->getController())->toBe(sprintf('%s::defaultAction', DefaultController::class));
});

it('uses the controller a test names', function () {
    $page = DocumentPageFactory::new()
        ->withController(DefaultController::class, 'javascriptAction')
        ->create();

    expect($page->getController())->toBe(sprintf('%s::javascriptAction', DefaultController::class));
});

it('writes a snippet', function () {
    $snippet = DocumentSnippetFactory::createOne();

    expect(reloaded($snippet))->toBeInstanceOf(Document\Snippet::class);
});

it('points a link at another document', function () {
    $target = DocumentPageFactory::createOne();

    $link = DocumentLinkFactory::new()
        ->withTarget($target)
        ->create();

    expect($link)
        ->toBeInstanceOf(Link::class)
        ->getInternal()
        ->toBe($target->getId())
        ->getLinktype()
        ->toBe('internal');
});

it('mirrors a document with a hardlink', function () {
    $source = DocumentPageFactory::createOne();

    $hardlink = DocumentHardlinkFactory::new()
        ->withSource($source)
        ->withLocale('de')
        ->create();

    expect($hardlink)
        ->toBeInstanceOf(Hardlink::class)
        ->getSourceId()
        ->toBe($source->getId())
        ->getChildrenFromSource()
        ->toBeTrue()
        ->getProperty('language')
        ->toBe('de');
});

it('links a document as the language variant of another', function () {
    $en = DocumentPageFactory::new()
        ->withLocale('en')
        ->create();

    $de = DocumentPageFactory::new()
        ->withLocale('de')
        ->withTranslationOf($en)
        ->create();

    $variants = (new Document\Service())->getTranslations($en);
    expect($variants)->toHaveKey('de', $de->getId());
});

it('joins a third document to the same set of variants', function () {
    $en = DocumentPageFactory::new()
        ->withLocale('en')
        ->create();
    $de = DocumentPageFactory::new()
        ->withLocale('de')
        ->withTranslationOf($en)
        ->create();

    $fr = DocumentPageFactory::new()
        ->withLocale('fr')
        ->withTranslationOf($de)
        ->create();

    $variants = (new Document\Service())->getTranslations($en);
    expect($variants)
        ->toHaveCount(3)
        ->toHaveKey('de', $de->getId())
        ->toHaveKey('en', $en->getId())
        ->toHaveKey('fr', $fr->getId());
});

it('links no language variant for a document that was never written', function () {
    $en = DocumentPageFactory::new()
        ->withLocale('en')
        ->create();

    DocumentPageFactory::new()
        ->withLocale('de')
        ->withTranslationOf($en)
        ->unsaved()
        ->create();

    $variants = (new Document\Service())->getTranslations($en);
    expect($variants)->not->toHaveKey('de');
});

it('names a page in the navigation after its key', function () {
    $page = DocumentPageFactory::createOne(['key' => 'contact']);

    expect($page->getProperty('navigation_name'))->toBe('contact');
});

it('names a document in the navigation as a test asks', function () {
    $link = DocumentLinkFactory::new()
        ->withNavigationName('Get in touch')
        ->create();

    expect($link->getProperty('navigation_name'))->toBe('Get in touch');
});
