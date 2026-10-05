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

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\SiteFactory;

dataset('an exact source and a regular expression', [
    'an exact source' => [
        fn (string $path): string => $path,
        false,
    ],
    'a regular expression' => [
        fn (string $path): string => '@^' . preg_quote($path, '@') . '$@',
        true,
    ],
]);

it('redirects a path with the status code of the redirect', function (int $statusCode) {
    $redirect = RedirectFactory::createOne([
        'source' => '/old-page',
        'target' => '/new-page',
        'statusCode' => $statusCode,
    ]);
    $response = answerTo('/old-page');

    expect($response)
        ->toRedirectTo('/new-page')
        ->toBeAnsweredBy($redirect)
        ->and($response->getStatusCode())
        ->toBe($statusCode);
})->with([
    301,
    302,
    307,
]);

it('answers with a status code that is no redirect without a location', function () {
    RedirectFactory::createOne([
        'source' => '/removed',
        'target' => '/anything',
        'statusCode' => 410,
    ]);
    $response = answerTo('/removed');

    expect($response->getStatusCode())
        ->toBe(410)
        ->and($response->headers->get('Location'))
        ->toBeNull();
});

it('compares a path regardless of case and accents', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/Über-Uns',
        'target' => '/about',
    ]);

    expect(answerTo('/uber-uns'))
        ->toBeAnsweredBy($redirect);
});

it('compares the part of the URL the type names', function (
    string $type,
    string $source,
    string $matching,
    string $other,
) {
    $redirect = RedirectFactory::createOne([
        'type' => $type,
        'source' => $source,
        'target' => '/target',
    ]);

    expect(answerTo($matching))
        ->toBeAnsweredBy($redirect)
        ->and(answerTo($other))
        ->toBeAnsweredBy(null);
})->with([
    'the path, without the query' => [
        Redirect::TYPE_PATH,
        '/shop',
        '/shop?id=5',
        '/shop/cart',
    ],
    'the path and the query' => [
        Redirect::TYPE_PATH_QUERY,
        '/shop?id=5',
        '/shop?id=5',
        '/shop?id=6',
    ],
    'the entire URI' => [
        Redirect::TYPE_ENTIRE_URI,
        'http://localhost/shop',
        'http://localhost/shop',
        'http://other.test/shop',
    ],
    'the path, for a redirect created automatically' => [
        Redirect::TYPE_AUTO_CREATE,
        '/moved',
        '/moved?x=1',
        '/moved/away',
    ],
]);

it('passes the query on when the redirect asks for it', function (bool $passThrough, string $location) {
    RedirectFactory::createOne([
        'source' => '/campaign',
        'target' => '/landing',
        'passThroughParameters' => $passThrough,
    ]);

    expect(answerTo('/campaign?utm=mail'))
        ->toRedirectTo($location);
})->with([
    'passed on' => [true, '/landing?utm=mail'],
    'dropped' => [false, '/landing'],
]);

it('redirects to the path of a target document', function () {
    $page = DocumentPageFactory::createOne([
        'key' => 'target-' . uniqid(),
    ]);
    RedirectFactory::createOne([
        'source' => '/to-document',
        'target' => (string) $page->getId(),
    ]);

    expect(answerTo('/to-document'))
        ->toRedirectTo($page->getFullPath());
});

it('does not redirect to a target document that no longer exists', function () {
    RedirectFactory::createOne([
        'source' => '/to-nowhere',
        'target' => '999999999',
    ]);
    $response = answerTo('/to-nowhere');

    expect($response)
        ->toBeAnsweredBy(null)
        ->and($response->getStatusCode())
        ->toBe(404);
});

it('replaces the back-references of a regular expression in the target', function () {
    RedirectFactory::createOne([
        'source' => '@^/blog/(\d+)/(\w+)$@',
        'target' => '/news/$2/$1',
        'regex' => true,
    ]);

    expect(answerTo('/blog/42/hello'))
        ->toRedirectTo('/news/hello/42');
});

it('takes the regular expression with the highest priority', function () {
    RedirectFactory::createOne([
        'source' => '@^/promo/@',
        'target' => '/low',
        'regex' => true,
        'priority' => 3,
    ]);
    $high = RedirectFactory::createOne([
        'source' => '@^/promo/.*@',
        'target' => '/high',
        'regex' => true,
        'priority' => 7,
    ]);

    expect(answerTo('/promo/summer'))
        ->toBeAnsweredBy($high);
});

it('takes an exact source before any regular expression', function () {
    $exact = RedirectFactory::createOne([
        'source' => '/sale',
        'target' => '/exact',
        'priority' => 1,
    ]);
    RedirectFactory::createOne([
        'source' => '@^/sale$@',
        'target' => '/regex',
        'regex' => true,
        'priority' => 10,
    ]);

    expect(answerTo('/sale'))
        ->toBeAnsweredBy($exact);
});

it('redirects away from an existing page with priority 99', function (callable $source, bool $regex) {
    $page = DocumentPageFactory::createOne([
        'key' => 'existing-' . uniqid(),
        'published' => true,
    ]);
    $redirect = RedirectFactory::createOne([
        'source' => $source($page->getFullPath()),
        'target' => '/elsewhere',
        'regex' => $regex,
        'priority' => 99,
    ]);

    expect(answerTo($page->getFullPath()))
        ->toBeAnsweredBy($redirect);
})->with('an exact source and a regular expression');

it('leaves an existing page alone with a priority below 99', function (callable $source, bool $regex) {
    $page = DocumentPageFactory::createOne([
        'key' => 'existing-' . uniqid(),
        'published' => true,
    ]);
    RedirectFactory::createOne([
        'source' => $source($page->getFullPath()),
        'target' => '/elsewhere',
        'regex' => $regex,
        'priority' => 10,
    ]);
    $response = answerTo($page->getFullPath());

    expect($response)
        ->toBeAnsweredBy(null)
        ->and($response->getStatusCode())
        ->toBe(200);
})->with('an exact source and a regular expression');

it('ignores a redirect that is inactive or has expired', function (callable $source, bool $regex, array $values) {
    RedirectFactory::createOne([
        'source' => $source('/timed'),
        'target' => '/later',
        'regex' => $regex,
        ...$values,
    ]);

    expect(answerTo('/timed'))
        ->toBeAnsweredBy(null);
})->with('an exact source and a regular expression')->with([
    'inactive' => [['active' => false]],
    'expired' => [['expiry' => time() - 60]],
]);

it('applies a redirect until it expires', function (callable $source, bool $regex) {
    $redirect = RedirectFactory::createOne([
        'source' => $source('/timed'),
        'target' => '/later',
        'regex' => $regex,
        'expiry' => time() + 3600,
    ]);

    expect(answerTo('/timed'))
        ->toBeAnsweredBy($redirect);
})->with('an exact source and a regular expression');

it('limits a redirect with a source site to that site', function (callable $source, bool $regex) {
    $site = SiteFactory::createOne();
    $redirect = RedirectFactory::createOne([
        'source' => $source('/local'),
        'target' => '/there',
        'regex' => $regex,
        'sourceSite' => $site->getId(),
    ]);

    expect(answerTo('http://' . $site->getMainDomain() . '/local'))
        ->toBeAnsweredBy($redirect)
        ->and(answerTo('http://localhost/local'))
        ->toBeAnsweredBy(null);
})->with('an exact source and a regular expression');

it('applies a redirect without a source site only outside of sites', function (callable $source, bool $regex) {
    $site = SiteFactory::createOne();
    $redirect = RedirectFactory::createOne([
        'source' => $source('/global'),
        'target' => '/there',
        'regex' => $regex,
    ]);

    expect(answerTo('/global'))
        ->toBeAnsweredBy($redirect)
        ->and(answerTo('http://' . $site->getMainDomain() . '/global'))
        ->toBeAnsweredBy(null);
})->with('an exact source and a regular expression');

it('redirects to the main domain of the target site', function () {
    $site = SiteFactory::createOne();
    RedirectFactory::createOne([
        'source' => '/to-site',
        'target' => '/welcome',
        'targetSite' => $site->getId(),
    ]);

    expect(answerTo('/to-site'))
        ->toRedirectTo('http://' . $site->getMainDomain() . '/welcome');
});

it('redirects once the redirect has started', function (callable $source, bool $regex) {
    $redirect = RedirectFactory::createOne([
        'source' => $source('/scheduled'),
        'target' => '/campaign',
        'regex' => $regex,
        'validFrom' => time() - 3600,
    ]);

    expect(answerTo('/scheduled'))
        ->toBeAnsweredBy($redirect);
})->with('an exact source and a regular expression');

it('does not redirect before the redirect starts', function (callable $source, bool $regex) {
    RedirectFactory::createOne([
        'source' => $source('/scheduled'),
        'target' => '/campaign',
        'regex' => $regex,
        'validFrom' => time() + 3600,
    ]);

    expect(answerTo('/scheduled'))
        ->toBeAnsweredBy(null);
})->with('an exact source and a regular expression');

it('takes a protected exact source before an unprotected one with a higher priority', function () {
    $protected = RedirectFactory::createOne([
        'source' => '/relaunch',
        'target' => '/kept',
        'priority' => 1,
        'protected' => true,
    ]);
    RedirectFactory::createOne([
        'source' => '/relaunch',
        'target' => '/override',
        'priority' => 10,
    ]);

    expect(answerTo('/relaunch'))
        ->toBeAnsweredBy($protected);
});

it('takes a protected regular expression before an unprotected exact source', function () {
    $protected = RedirectFactory::createOne([
        'source' => '@^/legacy/.*@',
        'target' => '/kept',
        'regex' => true,
        'protected' => true,
    ]);
    RedirectFactory::createOne([
        'source' => '/legacy/page',
        'target' => '/override',
        'priority' => 10,
    ]);

    expect(answerTo('/legacy/page'))
        ->toBeAnsweredBy($protected);
});

it('offers 410 Gone for removed content', function () {
    expect(Redirect::getStatusCodes())
        ->toHaveKey(410);
});

it('ignores a regular expression that does not compile', function () {
    RedirectFactory::createOne([
        'source' => '@^/broken(@',
        'target' => '/x',
        'regex' => true,
    ]);
    $valid = RedirectFactory::createOne([
        'source' => '@^/broken@',
        'target' => '/y',
        'regex' => true,
    ]);

    expect(answerTo('/broken('))
        ->toBeAnsweredBy($valid);
});
