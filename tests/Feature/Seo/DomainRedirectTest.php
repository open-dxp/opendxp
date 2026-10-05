<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Seo;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\SiteFactory;

it('redirects every path of a domain to the target', function (string $path) {
    $redirect = RedirectFactory::createOne([
        'type' => Redirect::TYPE_DOMAIN,
        'source' => 'summer.example.test',
        'target' => 'https://example.test/summer',
    ]);

    expect(answerTo('http://summer.example.test' . $path))
        ->toBeAnsweredBy($redirect)
        ->toRedirectTo('https://example.test/summer');
})->with([
    '/',
    '/tickets',
    '/a/deep/path?x=1',
]);

it('appends the path of the request when the redirect passes it through', function () {
    RedirectFactory::createOne([
        'type' => Redirect::TYPE_DOMAIN,
        'source' => 'old-brand.test',
        'target' => 'https://new-brand.test/',
        'passThroughPath' => true,
        'passThroughParameters' => true,
    ]);

    expect(answerTo('http://old-brand.test/products/shoes?size=42'))
        ->toRedirectTo('https://new-brand.test/products/shoes?size=42');
});

it('compares the domain regardless of case', function () {
    $redirect = RedirectFactory::createOne([
        'type' => Redirect::TYPE_DOMAIN,
        'source' => 'Event.Example.test',
        'target' => 'https://example.test/',
    ]);

    expect(answerTo('http://event.example.test/'))
        ->toBeAnsweredBy($redirect);
});

it('leaves other domains alone', function () {
    RedirectFactory::createOne([
        'type' => Redirect::TYPE_DOMAIN,
        'source' => 'summer.example.test',
        'target' => 'https://example.test/summer',
    ]);

    expect(answerTo('http://winter.example.test/'))
        ->toBeAnsweredBy(null);
});

it('redirects a domain before the site sends it to its main domain', function () {
    $site = SiteFactory::new()
        ->withDomains(['event.site.test'])
        ->create(['redirectToMainDomain' => true]);

    expect(answerTo('http://event.site.test/page')->headers->get('Location'))
        ->toStartWith('http://' . $site->getMainDomain());

    RedirectFactory::createOne([
        'type' => Redirect::TYPE_DOMAIN,
        'source' => 'event.site.test',
        'target' => 'https://campaign.test/',
    ]);
    nextRequest();

    expect(answerTo('http://event.site.test/page'))
        ->toRedirectTo('https://campaign.test/');
});

it('redirects a domain while the redirect is valid', function () {
    $redirect = RedirectFactory::createOne([
        'type' => Redirect::TYPE_DOMAIN,
        'source' => 'timed.example.test',
        'target' => 'https://example.test/',
        'validFrom' => time() - 3600,
    ]);

    expect(answerTo('http://timed.example.test/'))
        ->toBeAnsweredBy($redirect);
});

it('leaves a domain alone while the redirect is not valid', function (array $values) {
    RedirectFactory::createOne([
        'type' => Redirect::TYPE_DOMAIN,
        'source' => 'timed.example.test',
        'target' => 'https://example.test/',
        ...$values,
    ]);

    expect(answerTo('http://timed.example.test/'))
        ->toBeAnsweredBy(null);
})->with([
    'not yet valid' => [['validFrom' => time() + 3600]],
    'expired' => [['expiry' => time() - 60]],
    'inactive' => [['active' => false]],
]);
