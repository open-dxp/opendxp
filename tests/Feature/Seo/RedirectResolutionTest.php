<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Seo;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\SiteFactory;

it('redirects a path with the status code of the redirect', function (int $statusCode) {
    $redirect = RedirectFactory::createOne(['source' => '/old-page', 'target' => '/new-page', 'statusCode' => $statusCode]);

    expect(answerTo('/old-page'))
        ->status->toBe($statusCode)
        ->location->toEndWith('/new-page')
        ->redirect->toBe($redirect->getId());
})->with([301, 302, 307]);

it('answers with a status code that is no redirect without a location', function () {
    RedirectFactory::createOne(['source' => '/removed', 'target' => '/anything', 'statusCode' => 410]);

    expect(answerTo('/removed'))
        ->status->toBe(410)
        ->location->toBeNull();
});

it('compares a path regardless of case and accents', function () {
    $redirect = RedirectFactory::createOne(['source' => '/Über-Uns', 'target' => '/about']);

    expect(answerTo('/uber-uns')['redirect'])->toBe($redirect->getId());
});

it('compares the part of the URL the type names', function (string $type, string $source, string $matching, string $other) {
    $redirect = RedirectFactory::createOne(['type' => $type, 'source' => $source, 'target' => '/target']);

    expect(answerTo($matching)['redirect'])->toBe($redirect->getId())
        ->and(answerTo($other)['redirect'])->toBeNull();
})->with([
    'the path, without the query' => [Redirect::TYPE_PATH, '/shop', '/shop?id=5', '/shop/cart'],
    'the path and the query' => [Redirect::TYPE_PATH_QUERY, '/shop?id=5', '/shop?id=5', '/shop?id=6'],
    'the entire URI' => [Redirect::TYPE_ENTIRE_URI, 'http://localhost/shop', 'http://localhost/shop', 'http://other.test/shop'],
    'the path, for a redirect created automatically' => [Redirect::TYPE_AUTO_CREATE, '/moved', '/moved?x=1', '/moved/away'],
]);

it('passes the query on when the redirect asks for it', function (bool $passThrough, string $location) {
    RedirectFactory::createOne(['source' => '/campaign', 'target' => '/landing', 'passThroughParameters' => $passThrough]);

    expect(answerTo('/campaign?utm=mail')['location'])->toEndWith($location);
})->with([
    'passed on' => [true, '/landing?utm=mail'],
    'dropped' => [false, '/landing'],
]);

it('redirects to the path of a target document', function () {
    $page = DocumentPageFactory::createOne(['key' => 'target-' . uniqid()]);
    RedirectFactory::createOne(['source' => '/to-document', 'target' => (string) $page->getId()]);

    expect(answerTo('/to-document')['location'])->toEndWith($page->getFullPath());
});

it('does not redirect to a target document that no longer exists', function () {
    RedirectFactory::createOne(['source' => '/to-nowhere', 'target' => '999999999']);

    expect(answerTo('/to-nowhere'))
        ->status->toBe(404)
        ->redirect->toBeNull();
});

it('replaces the back-references of a regular expression in the target', function () {
    RedirectFactory::createOne(['source' => '@^/blog/(\d+)/(\w+)$@', 'target' => '/news/$2/$1', 'regex' => true]);

    expect(answerTo('/blog/42/hello')['location'])->toEndWith('/news/hello/42');
});

it('takes the regular expression with the highest priority', function () {
    RedirectFactory::createOne(['source' => '@^/promo/@', 'target' => '/low', 'regex' => true, 'priority' => 3]);
    $high = RedirectFactory::createOne(['source' => '@^/promo/.*@', 'target' => '/high', 'regex' => true, 'priority' => 7]);

    expect(answerTo('/promo/summer')['redirect'])->toBe($high->getId());
});

it('takes an exact source before any regular expression', function () {
    $exact = RedirectFactory::createOne(['source' => '/sale', 'target' => '/exact', 'priority' => 1]);
    RedirectFactory::createOne(['source' => '@^/sale$@', 'target' => '/regex', 'regex' => true, 'priority' => 10]);

    expect(answerTo('/sale')['redirect'])->toBe($exact->getId());
});

it('redirects away from an existing page only with priority 99', function (bool $regex, int $priority, bool $redirected) {
    $page = DocumentPageFactory::createOne(['key' => 'existing-' . uniqid(), 'published' => true]);
    $source = $regex ? '@^' . preg_quote($page->getFullPath(), '@') . '$@' : $page->getFullPath();
    RedirectFactory::createOne(['source' => $source, 'target' => '/elsewhere', 'regex' => $regex, 'priority' => $priority]);

    expect(answerTo($page->getFullPath())['status'])->toBe($redirected ? 301 : 200);
})->with([
    'an exact source with priority 99' => [false, 99, true],
    'an exact source with priority 10' => [false, 10, false],
    'a regular expression with priority 99' => [true, 99, true],
    'a regular expression with priority 10' => [true, 10, false],
]);

it('ignores a redirect that is inactive or has expired', function (bool $regex, array $values, bool $redirected) {
    RedirectFactory::createOne(['source' => $regex ? '@^/timed$@' : '/timed', 'target' => '/later', 'regex' => $regex, ...$values]);

    expect(answerTo('/timed')['status'])->toBe($redirected ? 301 : 404);
})->with([
    'an inactive exact source' => [false, ['active' => false], false],
    'an inactive regular expression' => [true, ['active' => false], false],
    'an expired exact source' => [false, ['expiry' => time() - 60], false],
    'an expired regular expression' => [true, ['expiry' => time() - 60], false],
    'an exact source that expires later' => [false, ['expiry' => time() + 3600], true],
    'a regular expression that expires later' => [true, ['expiry' => time() + 3600], true],
]);

it('limits a redirect with a source site to that site', function (bool $regex) {
    $site = SiteFactory::createOne();
    $redirect = RedirectFactory::createOne(['source' => $regex ? '@^/local$@' : '/local', 'target' => '/there', 'regex' => $regex, 'sourceSite' => $site->getId()]);

    expect(answerTo('http://' . $site->getMainDomain() . '/local')['redirect'])->toBe($redirect->getId())
        ->and(answerTo('http://localhost/local')['redirect'])->toBeNull();
})->with(['an exact source' => false, 'a regular expression' => true]);

it('applies a redirect without a source site only outside of sites', function (bool $regex) {
    $site = SiteFactory::createOne();
    $redirect = RedirectFactory::createOne(['source' => $regex ? '@^/global$@' : '/global', 'target' => '/there', 'regex' => $regex]);

    expect(answerTo('/global')['redirect'])->toBe($redirect->getId())
        ->and(answerTo('http://' . $site->getMainDomain() . '/global')['redirect'])->toBeNull();
})->with(['an exact source' => false, 'a regular expression' => true]);

it('redirects to the main domain of the target site', function () {
    $site = SiteFactory::createOne();
    RedirectFactory::createOne(['source' => '/to-site', 'target' => '/welcome', 'targetSite' => $site->getId()]);

    expect(answerTo('/to-site')['location'])->toBe('http://' . $site->getMainDomain() . '/welcome');
});

it('redirects only once the redirect is valid', function (bool $regex, int $validFrom, bool $redirected) {
    RedirectFactory::createOne(['source' => $regex ? '@^/scheduled$@' : '/scheduled', 'target' => '/campaign', 'regex' => $regex, 'validFrom' => $validFrom]);

    expect(answerTo('/scheduled')['status'])->toBe($redirected ? 301 : 404);
})->with([
    'an exact source that starts later' => [false, time() + 3600, false],
    'a regular expression that starts later' => [true, time() + 3600, false],
    'an exact source that has started' => [false, time() - 3600, true],
    'a regular expression that has started' => [true, time() - 3600, true],
]);

it('takes a protected exact source before an unprotected one with a higher priority', function () {
    $protected = RedirectFactory::createOne(['source' => '/relaunch', 'target' => '/kept', 'priority' => 1, 'protected' => true]);
    RedirectFactory::createOne(['source' => '/relaunch', 'target' => '/override', 'priority' => 10]);

    expect(answerTo('/relaunch')['redirect'])->toBe($protected->getId());
});

it('takes a protected regular expression before an unprotected exact source', function () {
    $protected = RedirectFactory::createOne(['source' => '@^/legacy/.*@', 'target' => '/kept', 'regex' => true, 'protected' => true]);
    RedirectFactory::createOne(['source' => '/legacy/page', 'target' => '/override', 'priority' => 10]);

    expect(answerTo('/legacy/page')['redirect'])->toBe($protected->getId());
});

it('offers 410 Gone for removed content', function () {
    expect(Redirect::getStatusCodes())->toHaveKey(410);
});

it('ignores a regular expression that does not compile', function () {
    RedirectFactory::createOne(['source' => '@^/broken(@', 'target' => '/x', 'regex' => true]);
    $valid = RedirectFactory::createOne(['source' => '@^/broken@', 'target' => '/y', 'regex' => true]);

    expect(answerTo('/broken(')['redirect'])->toBe($valid->getId());
});
