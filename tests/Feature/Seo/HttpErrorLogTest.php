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

use OpenDxp;
use OpenDxp\Bundle\SeoBundle\EventListener\ResponseExceptionListener;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectTableProvider;
use OpenDxp\Db;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

/**
 * Logs the error as a request outside of debug mode does. The test application runs in debug mode, which logs nothing.
 */
function logHttpError(string $uri, Throwable $exception): void
{
    $listener = new ResponseExceptionListener(
        Db::get(),
        Container::get(RedirectTableProvider::class),
        new NullLogger(),
        debug: false,
    );
    $listener->setOpenDxpContextResolver(Container::get(OpenDxpContextResolver::class));

    $request = Request::create($uri);
    $request->attributes->set(
        OpenDxpContextResolver::ATTRIBUTE_OPENDXP_CONTEXT,
        OpenDxpContextResolver::CONTEXT_DEFAULT,
    );

    $listener->onKernelException(new ExceptionEvent(
        OpenDxp::getKernel(),
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $exception,
    ));
    $listener->onKernelTerminate();
}

function timesLogged(string $uri): int
{
    return (int) Db::get()->fetchOne(
        'SELECT count FROM http_error_log WHERE uri = ?',
        [$uri],
    );
}

function loggedStatusCode(string $uri): ?int
{
    $code = Db::get()->fetchOne(
        'SELECT code FROM http_error_log WHERE uri = ?',
        [$uri],
    );

    return $code === false ? null : (int) $code;
}

it('logs a URL that is not found as a 404', function () {
    $uri = 'http://localhost/missing';

    logHttpError($uri, new NotFoundHttpException());

    expect(loggedStatusCode($uri))->toBe(404);
});

it('counts each request to a logged URL', function () {
    $uri = 'http://localhost/missing';
    logHttpError($uri, new NotFoundHttpException());
    logHttpError($uri, new NotFoundHttpException());

    logHttpError($uri, new NotFoundHttpException());

    expect(timesLogged($uri))->toBe(3);
});

it('logs any other error as a server error', function () {
    $uri = 'http://localhost/broken';

    logHttpError($uri, new RuntimeException('Broken'));

    expect(loggedStatusCode($uri))
        ->toBe(500)
        ->and(timesLogged($uri))
        ->toBe(1);
});

it('logs nothing in debug mode', function () {
    $uri = 'http://localhost/debugged';

    answerTo($uri);

    expect(timesLogged($uri))->toBe(0);
});

it('does not log a URL that a redirect answers', function () {
    RedirectFactory::createOne(['source' => '/redirected-away']);
    resetServices();

    answerTo('http://localhost/redirected-away');

    expect(timesLogged('http://localhost/redirected-away'))->toBe(0);
});

it('offers the path and the site of a logged URL', function () {
    $site = SiteFactory::createOne();
    $uri = sprintf(
        'http://%s/old/%s?x=1',
        $site->getMainDomain(),
        rawurlencode('Über uns'),
    );
    logHttpError($uri, new NotFoundHttpException());
    $admin = UserFactory::new()
        ->admin()
        ->create();

    $browser = Browser::actingAs($admin)
        ->post('/admin/bundle/seo/http-error-log', [
            'body' => ['filter' => $site->getMainDomain()],
        ]);

    $logged = json_decode($browser->content(), true)['items'];
    expect($logged)
        ->toHaveCount(1)
        ->and($logged[0])
        ->toMatchArray([
            'path' => '/old/Über uns',
            'siteId' => $site->getId(),
        ]);
});

it('removes a URL from the log', function () {
    $uri = 'http://localhost/handled';
    logHttpError($uri, new NotFoundHttpException());
    $admin = UserFactory::new()
        ->admin()
        ->create();
    $query = http_build_query(['uri' => $uri]);

    Browser::actingAs($admin)
        ->delete(sprintf('/admin/bundle/seo/http-error-log-entry?%s', $query));

    expect(timesLogged($uri))->toBe(0);
});
