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

namespace OpenDxp\Tests\Feature\SiteDeclaration;

use Carbon\Carbon;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\SiteFactory;

it('serves the default robots.txt to a site without content', function () {
    $response = answerTo('/robots.txt');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getContent())
        ->toBe("User-agent: *\nDisallow:");
});

it('serves the content of a site with the content type of the declaration', function () {
    $this->saveContent('security_txt', 0, "Contact: mailto:security@example.test\nExpires: 2027-01-01T00:00:00Z");

    $response = answerTo('/.well-known/security.txt');

    expect($response->getContent())
        ->toBe("Contact: mailto:security@example.test\nExpires: 2027-01-01T00:00:00Z")
        ->and($response->headers->get('Content-Type'))
        ->toBe('text/plain; charset=utf-8')
        ->and($response->headers->get('Cache-Control'))
        ->toBe('no-store, private');
});

it('replaces %expires% with the date 364 days after the day of the request', function () {
    Carbon::setTestNow('2026-10-20 23:59:00 UTC');
    $this->saveContent('security_txt', 0, "Contact: mailto:security@example.test\nExpires: %expires%");

    $response = answerTo('/.well-known/security.txt');

    expect($response->getContent())->toBe("Contact: mailto:security@example.test\nExpires: 2027-10-19T00:00:00Z");
})->after(fn () => Carbon::setTestNow());

it('serves the content of the site that owns the host', function (string $host) {
    $site = SiteFactory::new()
        ->withDomains(['www.shop.test'])
        ->create(['mainDomain' => 'shop.test']);

    $this->saveContent('ads_txt', 0, 'main.test, 1, DIRECT');
    $this->saveContent('ads_txt', $site->getId(), 'shop.test, 2, DIRECT');

    $response = answerTo(sprintf('http://%s/ads.txt', $host));

    expect($response->getContent())->toBe('shop.test, 2, DIRECT');
})->with([
    'the main domain' => ['shop.test'],
    'an additional domain' => ['www.shop.test'],
]);

it('serves the content of the main site to a host without a site', function () {
    $this->saveContent('ads_txt', 0, 'main.test, 1, DIRECT');

    $response = answerTo('http://unknown.test/ads.txt');

    expect($response->getContent())->toBe('main.test, 1, DIRECT');
});

it('does not fall back to the content of the main site', function () {
    SiteFactory::createOne(['mainDomain' => 'shop.test']);

    $this->saveContent('ads_txt', 0, 'main.test, 1, DIRECT');

    $response = answerTo('http://shop.test/ads.txt');

    expect($response->getStatusCode())->toBe(404);
});

it('answers an enabled declaration without content with 404, even when a document has its path', function () {
    DocumentPageFactory::createOne(['key' => 'ads.txt']);

    $response = answerTo('/ads.txt');

    expect($response->getStatusCode())->toBe(404);
});

it('leaves the path of a disabled declaration to a document', function () {
    DocumentPageFactory::createOne(['key' => 'llms.txt']);

    $response = answerTo('/llms.txt');

    expect($response->getStatusCode())->toBe(200);
});

it('serves the declaration of another bundle', function () {
    $this->saveContent('apple_app_site_association', 0, '{"applinks": {}}');

    $response = answerTo('/.well-known/apple-app-site-association');

    expect($response->getContent())
        ->toBe('{"applinks": {}}')
        ->and($response->headers->get('Content-Type'))
        ->toBe('application/json');
});
