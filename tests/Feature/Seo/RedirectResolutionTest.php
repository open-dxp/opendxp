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

namespace OpenDxp\Tests\Feature\Seo;

use Closure;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\SiteFactory;

dataset('redirect sources', [
    'an exact source' => [
        fn (string $path): RedirectFactory => RedirectFactory::new()
            ->with(['source' => $path]),
    ],
    'a regular expression' => [
        fn (string $path): RedirectFactory => RedirectFactory::new()
            ->matching(sprintf('@^%s$@', preg_quote($path, '@'))),
    ],
]);

it('redirects a path with the status code of the redirect', function (int $statusCode) {
    $redirect = RedirectFactory::new()
        ->withStatusCode($statusCode)
        ->create([
            'source' => '/old-page',
            'target' => '/new-page',
        ]);

    $response = answerTo('/old-page');

    expect($response)
        ->toRedirectTo('/new-page')
        ->toComeFrom($redirect)
        ->and($response->getStatusCode())
        ->toBe($statusCode);
})->with([
    'moved permanently' => [301],
    'found' => [302],
    'temporary redirect' => [307],
]);

it('answers 410 Gone without a location', function () {
    RedirectFactory::new()
        ->withStatusCode(410)
        ->create(['source' => '/removed']);

    $response = answerTo('/removed');

    expect($response->getStatusCode())
        ->toBe(410)
        ->and($response->headers->get('Location'))
        ->toBeNull();
});

it('compares a path regardless of case and accents', function () {
    $redirect = RedirectFactory::createOne(['source' => '/Über-Uns']);

    $response = answerTo('/uber-uns');

    expect($response)->toComeFrom($redirect);
});

it('redirects a URL that matches in the part the redirect type selects', function (
    string $type,
    string $source,
    string $url,
) {
    $redirect = RedirectFactory::createOne([
        'type' => $type,
        'source' => $source,
    ]);

    $response = answerTo($url);

    expect($response)->toComeFrom($redirect);
})->with([
    'the path, without the query' => [Redirect::TYPE_PATH, '/shop', '/shop?id=5'],
    'the path and the query' => [Redirect::TYPE_PATH_QUERY, '/shop?id=5', '/shop?id=5'],
    'the entire URI' => [Redirect::TYPE_ENTIRE_URI, 'http://localhost/shop', 'http://localhost/shop'],
    'the path of a redirect created automatically' => [Redirect::TYPE_AUTO_CREATE, '/moved', '/moved?x=1'],
]);

it('ignores a URL that differs in the part the redirect type selects', function (
    string $type,
    string $source,
    string $url,
) {
    RedirectFactory::createOne([
        'type' => $type,
        'source' => $source,
    ]);

    $response = answerTo($url);

    expect($response)->toComeFromNoRedirect();
})->with([
    'the path' => [Redirect::TYPE_PATH, '/shop', '/shop/cart'],
    'the query' => [Redirect::TYPE_PATH_QUERY, '/shop?id=5', '/shop?id=6'],
    'the host of the entire URI' => [Redirect::TYPE_ENTIRE_URI, 'http://localhost/shop', 'http://other.test/shop'],
    'the path of a redirect created automatically' => [Redirect::TYPE_AUTO_CREATE, '/moved', '/moved/away'],
]);

it('passes the query on when the redirect asks for it', function () {
    RedirectFactory::new()
        ->passingThroughParameters()
        ->create([
            'source' => '/campaign',
            'target' => '/landing',
        ]);

    $response = answerTo('/campaign?utm=mail');

    expect($response)->toRedirectTo('/landing?utm=mail');
});

it('drops the query by default', function () {
    RedirectFactory::createOne([
        'source' => '/campaign',
        'target' => '/landing',
    ]);

    $response = answerTo('/campaign?utm=mail');

    expect($response)->toRedirectTo('/landing');
});

it('redirects to the path of a target document', function () {
    $page = DocumentPageFactory::createOne();
    RedirectFactory::new()
        ->toDocument($page)
        ->create(['source' => '/to-document']);

    $response = answerTo('/to-document');

    expect($response)->toRedirectTo($page->getFullPath());
});

it('does not redirect to a target document that no longer exists', function () {
    RedirectFactory::createOne([
        'source' => '/to-nowhere',
        'target' => '999999999',
    ]);

    $response = answerTo('/to-nowhere');

    expect($response)
        ->toComeFromNoRedirect()
        ->and($response->getStatusCode())
        ->toBe(404);
});

it('replaces the back-references of a regular expression in the target', function () {
    RedirectFactory::new()
        ->matching('@^/blog/(\d+)/(\w+)$@')
        ->create(['target' => '/news/$2/$1']);

    $response = answerTo('/blog/42/hello');

    expect($response)->toRedirectTo('/news/hello/42');
});

it('takes the regular expression with the highest priority', function () {
    RedirectFactory::new()
        ->matching('@^/promo/@')
        ->withPriority(3)
        ->create();
    $high = RedirectFactory::new()
        ->matching('@^/promo/.*@')
        ->withPriority(7)
        ->create();

    $response = answerTo('/promo/summer');

    expect($response)->toComeFrom($high);
});

it('takes an exact source before any regular expression', function () {
    $exact = RedirectFactory::new()
        ->withPriority(1)
        ->create(['source' => '/sale']);
    RedirectFactory::new()
        ->matching('@^/sale$@')
        ->withPriority(10)
        ->create();

    $response = answerTo('/sale');

    expect($response)->toComeFrom($exact);
});

it('redirects away from an existing page with priority 99', function (Closure $redirectFrom) {
    $page = DocumentPageFactory::createOne();
    $redirect = $redirectFrom($page->getFullPath())
        ->withPriority(99)
        ->create();

    $response = answerTo($page->getFullPath());

    expect($response)->toComeFrom($redirect);
})->with('redirect sources');

it('leaves an existing page alone with a priority below 99', function (Closure $redirectFrom) {
    $page = DocumentPageFactory::createOne();
    $redirectFrom($page->getFullPath())
        ->withPriority(10)
        ->create();

    $response = answerTo($page->getFullPath());

    expect($response)
        ->toComeFromNoRedirect()
        ->and($response->getStatusCode())
        ->toBe(200);
})->with('redirect sources');

it('applies a redirect while it is in effect', function (Closure $redirectFrom, Closure $period) {
    $factory = $redirectFrom('/timed');
    $redirect = $period($factory)->create();

    $response = answerTo('/timed');

    expect($response)->toComeFrom($redirect);
})
    ->with('redirect sources')
    ->with([
        'after its start' => [fn (RedirectFactory $factory): RedirectFactory => $factory->started()],
        'before its expiry' => [fn (RedirectFactory $factory): RedirectFactory => $factory->expiring()],
    ]);

it('ignores a redirect that is not in effect', function (Closure $redirectFrom, Closure $period) {
    $factory = $redirectFrom('/timed');
    $period($factory)->create();

    $response = answerTo('/timed');

    expect($response)->toComeFromNoRedirect();
})
    ->with('redirect sources')
    ->with([
        'before its start' => [fn (RedirectFactory $factory): RedirectFactory => $factory->scheduled()],
        'after its expiry' => [fn (RedirectFactory $factory): RedirectFactory => $factory->expired()],
        'while inactive' => [fn (RedirectFactory $factory): RedirectFactory => $factory->inactive()],
    ]);

it('applies a redirect with a source site on that site', function (Closure $redirectFrom) {
    $site = SiteFactory::createOne();
    $redirect = $redirectFrom('/local')
        ->forSite($site)
        ->create();

    $response = answerTo(sprintf('http://%s/local', $site->getMainDomain()));

    expect($response)->toComeFrom($redirect);
})->with('redirect sources');

it('ignores a redirect with a source site outside of that site', function (Closure $redirectFrom) {
    $site = SiteFactory::createOne();
    $redirectFrom('/local')
        ->forSite($site)
        ->create();

    $response = answerTo('http://localhost/local');

    expect($response)->toComeFromNoRedirect();
})->with('redirect sources');

it('applies a redirect without a source site outside of sites', function (Closure $redirectFrom) {
    $redirect = $redirectFrom('/global')->create();

    $response = answerTo('/global');

    expect($response)->toComeFrom($redirect);
})->with('redirect sources');

it('ignores a redirect without a source site on a site', function (Closure $redirectFrom) {
    $site = SiteFactory::createOne();
    $redirectFrom('/global')->create();

    $response = answerTo(sprintf('http://%s/global', $site->getMainDomain()));

    expect($response)->toComeFromNoRedirect();
})->with('redirect sources');

it('redirects to the main domain of the target site', function () {
    $site = SiteFactory::createOne();
    RedirectFactory::new()
        ->toSite($site)
        ->create([
            'source' => '/to-site',
            'target' => '/welcome',
        ]);

    $response = answerTo('/to-site');

    expect($response)->toRedirectTo(sprintf('http://%s/welcome', $site->getMainDomain()));
});

it('takes a protected exact source before an unprotected one with a higher priority', function () {
    $protected = RedirectFactory::new()
        ->protected()
        ->withPriority(1)
        ->create(['source' => '/relaunch']);
    RedirectFactory::new()
        ->withPriority(10)
        ->create(['source' => '/relaunch']);

    $response = answerTo('/relaunch');

    expect($response)->toComeFrom($protected);
});

it('takes a protected regular expression before an unprotected exact source', function () {
    $protected = RedirectFactory::new()
        ->matching('@^/legacy/.*@')
        ->protected()
        ->create();
    RedirectFactory::new()
        ->withPriority(10)
        ->create(['source' => '/legacy/page']);

    $response = answerTo('/legacy/page');

    expect($response)->toComeFrom($protected);
});

it('ignores a regular expression that does not compile', function () {
    RedirectFactory::new()
        ->matching('@^/broken(@')
        ->create();
    $valid = RedirectFactory::new()
        ->matching('@^/broken@')
        ->create();

    $response = answerTo('/broken(');

    expect($response)->toComeFrom($valid);
});
