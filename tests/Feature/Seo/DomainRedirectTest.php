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

use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\SiteFactory;

it('redirects every path of a domain to the target', function (string $path) {
    $redirect = RedirectFactory::new()
        ->forDomain('summer.example.test')
        ->create(['target' => 'https://example.test/summer']);

    $response = answerTo(sprintf('http://summer.example.test%s', $path));

    expect($response)
        ->toBeAnsweredBy($redirect)
        ->toRedirectTo('https://example.test/summer');
})->with([
    'the root' => ['/'],
    'a path' => ['/tickets'],
    'a deep path with a query' => ['/a/deep/path?x=1'],
]);

it('appends the path of the request when the redirect passes it through', function () {
    RedirectFactory::new()
        ->forDomain('old-brand.test')
        ->passingThroughPath()
        ->passingThroughParameters()
        ->create(['target' => 'https://new-brand.test/']);

    $response = answerTo('http://old-brand.test/products/shoes?size=42');

    expect($response)->toRedirectTo('https://new-brand.test/products/shoes?size=42');
});

it('appends the path in front of the query of the target', function () {
    RedirectFactory::new()
        ->forDomain('campaign.test')
        ->passingThroughPath()
        ->create(['target' => 'https://example.test/?utm_source=campaign']);

    $response = answerTo('http://campaign.test/shoes');

    expect($response)->toRedirectTo('https://example.test/shoes?utm_source=campaign');
});

it('compares the domain regardless of case', function () {
    $redirect = RedirectFactory::new()
        ->forDomain('Event.Example.test')
        ->create(['target' => 'https://example.test/']);

    $response = answerTo('http://event.example.test/');

    expect($response)->toBeAnsweredBy($redirect);
});

it('leaves other domains alone', function () {
    RedirectFactory::new()
        ->forDomain('summer.example.test')
        ->create(['target' => 'https://example.test/summer']);

    $response = answerTo('http://winter.example.test/');

    expect($response)->toBeAnsweredWithoutRedirect();
});

it('redirects a domain before the site sends it to its main domain', function () {
    SiteFactory::new()
        ->withDomains(['event.site.test'])
        ->create(['redirectToMainDomain' => true]);
    RedirectFactory::new()
        ->forDomain('event.site.test')
        ->create(['target' => 'https://campaign.test/']);
    resetServices();

    $response = answerTo('http://event.site.test/page');

    expect($response)->toRedirectTo('https://campaign.test/');
});

it('redirects a domain while the redirect is in effect', function (RedirectFactory $factory) {
    $redirect = $factory
        ->forDomain('timed.example.test')
        ->create(['target' => 'https://example.test/']);

    $response = answerTo('http://timed.example.test/');

    expect($response)->toBeAnsweredBy($redirect);
})->with([
    'after its start' => [
        fn () => RedirectFactory::new()
            ->started(),
    ],
    'before its expiry' => [
        fn () => RedirectFactory::new()
            ->expiring(),
    ],
]);

it('leaves a domain alone while the redirect is not in effect', function (RedirectFactory $factory) {
    $factory
        ->forDomain('timed.example.test')
        ->create(['target' => 'https://example.test/']);

    $response = answerTo('http://timed.example.test/');

    expect($response)->toBeAnsweredWithoutRedirect();
})->with([
    'before its start' => [
        fn () => RedirectFactory::new()
            ->scheduled(),
    ],
    'after its expiry' => [
        fn () => RedirectFactory::new()
            ->expired(),
    ],
    'while inactive' => [
        fn () => RedirectFactory::new()
            ->inactive(),
    ],
]);
