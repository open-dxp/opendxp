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

use OpenDxp\Http\RequestHelper;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink\Service as HardlinkService;
use OpenDxp\Model\Document\Hardlink\Wrapper\WrapperInterface;
use OpenDxp\Model\Site;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\DocumentSnippetFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Story\TwoSites;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

function frontendRequest(string $url): Request
{
    $request = Request::create($url);
    $request->attributes->set(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST, true);

    return $request;
}

function contentRequest(string $url, Document|WrapperInterface $content): Request
{
    $request = frontendRequest($url);
    $request->attributes->set(DynamicRouter::CONTENT_KEY, $content);

    return $request;
}

function hardlinkRequest(): Request
{
    $hardlink = HardlinkService::wrap(TwoSites::hardlink());

    return contentRequest('http://domain-b.test/hardlink/', $hardlink);
}

/**
 * OpenDXP reads the main request to resolve a path across sites. The request therefore replaces whatever the test
 * case put on the stack instead of going on top of it.
 */
function serve(Request $request): void
{
    $stack = Container::requestStack();

    while ($stack->getCurrentRequest() !== null) {
        $stack->pop();
    }

    $stack->push($request);
}

beforeEach(fn () => TwoSites::load());

it('writes the path in the tree when no site is served', function () {
    $page = DocumentPageFactory::createOne();
    serve(frontendRequest('http://example.test/no-site-page'));

    $path = $page->getFullPath(true);

    expect($path)->toBe($page->getRealFullPath());
});

it('writes the url of the other site for a preview served from another domain', function () {
    serve(frontendRequest('http://domain-b.test/some-path?opendxp_preview=1'));

    $path = TwoSites::subpage()->getFullPath(true);

    expect($path)->toBe('http://domain-a.test/section/subpage');
});

describe('while the second site is served', function () {
    beforeEach(fn () => Site::setCurrentSite(TwoSites::siteB()));

    it('writes a path without a domain for a document of that site', function () {
        $page = DocumentPageFactory::new()
            ->withParent(TwoSites::siteB()->getRootDocument())
            ->create(['key' => 'b-page']);
        serve(frontendRequest('http://domain-b.test/b-page'));

        $path = $page->getFullPath(true);

        expect($path)->toBe('/b-page');
    });

    it('writes a slash for the root of that site', function () {
        $root = TwoSites::siteB()->getRootDocument();
        serve(frontendRequest('http://domain-b.test/'));

        $path = $root->getFullPath(true);

        expect($path)->toBe('/');
    });

    it('writes the url of the main domain for a document that belongs to no site', function () {
        $page = DocumentPageFactory::createOne();
        serve(frontendRequest('http://domain-b.test/'));

        $path = $page->getFullPath(true);

        expect($path)->toBe(sprintf('http://opendxp-testing.test%s', $page->getRealFullPath()));
    });

    it('writes the path through the hardlink for a document of the first site', function () {
        serve(hardlinkRequest());

        $path = TwoSites::subpage()->getFullPath(true);

        expect($path)->toBe('/hardlink/subpage');
    });

    it('writes the path through the hardlink while a snippet is rendered', function () {
        serve(hardlinkRequest());
        // The snippet is the current request now, and it carries no hardlink of its own.
        $snippet = DocumentSnippetFactory::createOne();
        Container::requestStack()->push(contentRequest('http://domain-b.test/hardlink/', $snippet));

        $path = TwoSites::subpage()->getFullPath(true);

        expect($path)->toBe('/hardlink/subpage');
    });

    it('writes the url of the first site for its root document', function () {
        $root = TwoSites::siteA()->getRootDocument();
        serve(frontendRequest('http://domain-b.test/'));

        $path = $root->getFullPath(true);

        expect($path)->toBe('http://domain-a.test/');
    });

    it('writes the url of the first site when no hardlink is served', function () {
        serve(frontendRequest('http://domain-b.test/b-page'));

        $path = TwoSites::subpage()->getFullPath(true);

        expect($path)->toBe('http://domain-a.test/section/subpage');
    });

    it('writes the url of the first site for a document the hardlink does not cover', function () {
        $page = DocumentPageFactory::new()
            ->withParent(TwoSites::siteA()->getRootDocument())
            ->create(['key' => 'other-page']);
        serve(hardlinkRequest());

        $path = $page->getFullPath(true);

        expect($path)->toBe('http://domain-a.test/other-page');
    });

    it('writes the url of the first site when the hardlink source is gone', function () {
        $page = DocumentPageFactory::new()
            ->withParent(TwoSites::siteA()->getRootDocument())
            ->create(['key' => 'survivor-page']);
        $request = hardlinkRequest();
        TwoSites::section()->delete();
        serve($request);

        $path = $page->getFullPath(true);

        expect($path)->toBe('http://domain-a.test/survivor-page');
    });
});
