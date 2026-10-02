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


namespace OpenDxp\Tests\Feature\Document;

use OpenDxp\Model\Document\Hardlink\Service as HardlinkService;
use OpenDxp\Model\Document\Hardlink\Wrapper\WrapperInterface;
use OpenDxp\Model\Site;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\DocumentSnippetFactory;
use OpenDxp\Tests\Story\TwoSites;

function hardlinkPath(): string
{
    return str_replace(
        TwoSites::get('siteB')->getRootDocument()->getRealFullPath(),
        '',
        TwoSites::get('hardlink')->getRealFullPath(),
    );
}

function wrappedHardlink(): WrapperInterface
{
    return HardlinkService::wrap(TwoSites::get('hardlink'));
}

it('writes a path without a domain for a document of the site being served', function () {

    TwoSites::load();
    $page = DocumentPageFactory::createOne([
        'parentId' => TwoSites::get('siteB')->getRootDocument()->getId(),
        'key' => 'b-page',
    ]);

    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/b-page');

    expect($page->getFullPath(true))->toBe('/b-page');
});

it('writes a slash for the root of the site being served', function () {

    TwoSites::load();
    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/');

    expect(TwoSites::get('siteB')->getRootDocument()->getFullPath(true))->toBe('/');
});

it('writes the path in the tree when no site is being served', function () {

    $page = DocumentPageFactory::createOne();
    frontendRequest('http://example.test/no-site-page');

    expect($page->getFullPath(true))->toBe($page->getPath() . $page->getKey());
});

it('writes the path through the hardlink for a document of the other site', function () {

    TwoSites::load();
    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/hl/', wrappedHardlink());

    expect(TwoSites::get('subpage')->getFullPath(true))->toBe(hardlinkPath() . '/subpage');
});

it('writes the path through the hardlink even while a snippet is being rendered', function () {

    TwoSites::load();
    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/hl/', wrappedHardlink());

    // The snippet is the current request now, and it carries no hardlink of its own.
    subRequest('http://domain-b.test/hl/', DocumentSnippetFactory::createOne());

    expect(TwoSites::get('subpage')->getFullPath(true))->toBe(hardlinkPath() . '/subpage');
});

it('writes the url of the other site for its root document', function () {

    TwoSites::load();
    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/');

    expect(TwoSites::get('siteA')->getRootDocument()->getFullPath(true))->toBe('http://domain-a.test/');
});

it('writes the url of the other site when no hardlink is being served', function () {

    TwoSites::load();
    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/b-page');

    expect(TwoSites::get('subpage')->getFullPath(true))
        ->toStartWith('http://domain-a.test')
        ->toContain('/section/subpage');
});

it('writes the url of the other site for a document the hardlink does not cover', function () {

    TwoSites::load();
    $outside = DocumentPageFactory::createOne([
        'parentId' => TwoSites::get('siteA')->getRootDocument()->getId(),
        'key' => 'other-page',
    ]);

    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/hl/', wrappedHardlink());

    expect($outside->getFullPath(true))
        ->toStartWith('http://domain-a.test')
        ->toContain('/other-page');
});

it('writes the url of the other site when the hardlink source is gone', function () {

    TwoSites::load();
    $survivor = DocumentPageFactory::createOne([
        'parentId' => TwoSites::get('siteA')->getRootDocument()->getId(),
        'key' => 'survivor-page',
    ]);
    $hardlink = wrappedHardlink();

    TwoSites::get('section')->delete();

    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/hl/', $hardlink);

    expect($survivor->getFullPath(true))->toStartWith('http://');
});

it('writes the url of the other site for a preview served from another domain', function () {

    TwoSites::load();
    frontendRequest('http://domain-b.test/some-path?opendxp_preview=1');

    expect(TwoSites::get('subpage')->getFullPath(true))
        ->toStartWith('http://domain-a.test')
        ->toContain('/section/subpage');
});

it('writes the path in the tree for a document that belongs to no site', function () {

    TwoSites::load();
    $orphan = DocumentPageFactory::createOne(['parentId' => 1]);

    Site::setCurrentSite(TwoSites::get('siteB'));
    frontendRequest('http://domain-b.test/');

    expect($orphan->getFullPath(true))->not->toContain('domain-b.test');
});
