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
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('logs a URL that is not found once and counts each request to it', function () {
    $uri = 'http://localhost/missing-' . uniqid();

    logHttpError($uri, new NotFoundHttpException());
    logHttpError($uri, new NotFoundHttpException());
    logHttpError($uri, new NotFoundHttpException());

    expect(loggedStatusCode($uri))
        ->toBe(404)
        ->and(timesLogged($uri))
        ->toBe(3);
});

it('logs any other error as a server error', function () {
    $uri = 'http://localhost/broken-' . uniqid();

    logHttpError($uri, new RuntimeException('Broken'));

    expect(loggedStatusCode($uri))
        ->toBe(500)
        ->and(timesLogged($uri))
        ->toBe(1);
});

it('logs nothing in debug mode', function () {
    $uri = 'http://localhost/debugged-' . uniqid();

    answerTo($uri);

    expect(timesLogged($uri))
        ->toBe(0);
});

it('does not log a URL that a redirect answers', function () {
    RedirectFactory::createOne([
        'source' => '/redirected-away',
        'target' => '/target',
    ]);
    nextRequest();

    answerTo('http://localhost/redirected-away');

    expect(timesLogged('http://localhost/redirected-away'))
        ->toBe(0);
});

it('offers the path and the site of a logged URL', function () {
    $site = SiteFactory::createOne();
    $uri = 'http://' . $site->getMainDomain() . '/old/' . rawurlencode('Über uns') . '?x=1';
    logHttpError($uri, new NotFoundHttpException());

    $response = Browser::actingAs(UserFactory::new()->admin()->create())
        ->post('/admin/bundle/seo/http-error-log', [
            'body' => ['filter' => $site->getMainDomain()],
        ])
        ->assertSuccessful()
        ->content();
    $logged = json_decode($response, true)['items'];

    expect($logged)
        ->toHaveCount(1)
        ->and($logged[0])
        ->toMatchArray([
            'path' => '/old/Über uns',
            'siteId' => $site->getId(),
        ]);
});

it('removes a URL from the log', function () {
    $uri = 'http://localhost/handled-' . uniqid();
    logHttpError($uri, new NotFoundHttpException());

    Browser::actingAs(UserFactory::new()->admin()->create())
        ->delete('/admin/bundle/seo/http-error-log-entry?' . http_build_query(['uri' => $uri]))
        ->assertSuccessful();

    expect(timesLogged($uri))
        ->toBe(0);
});
