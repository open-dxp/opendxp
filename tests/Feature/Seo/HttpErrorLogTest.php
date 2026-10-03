<?php

declare(strict_types=1);

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
 * @return list<array{uri: string, code: int, count: int}>
 */
function loggedErrors(string $uri): array
{
    return array_map(
        static fn (array $row): array => ['uri' => $row['uri'], 'code' => (int) $row['code'], 'count' => (int) $row['count']],
        Db::get()->fetchAllAssociative('SELECT uri, code, count FROM http_error_log WHERE uri = ?', [$uri])
    );
}

/**
 * The test application runs in debug mode, which logs no HTTP errors. A listener of its own runs without it.
 */
function logHttpError(string $uri, Throwable $exception): void
{
    $listener = new ResponseExceptionListener(Db::get(), Container::get(RedirectTableProvider::class), new NullLogger(), debug: false);
    $listener->setOpenDxpContextResolver(Container::get(OpenDxpContextResolver::class));

    $request = Request::create($uri);
    $request->attributes->set(OpenDxpContextResolver::ATTRIBUTE_OPENDXP_CONTEXT, OpenDxpContextResolver::CONTEXT_DEFAULT);

    $listener->onKernelException(new ExceptionEvent(OpenDxp::getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $exception));
    $listener->onKernelTerminate();
}

it('logs a URL that is not found once and counts each request to it', function () {
    $uri = 'http://localhost/missing-' . uniqid();

    logHttpError($uri, new NotFoundHttpException());
    logHttpError($uri, new NotFoundHttpException());
    logHttpError($uri, new NotFoundHttpException());

    expect(loggedErrors($uri))->toBe([['uri' => $uri, 'code' => 404, 'count' => 3]]);
});

it('logs any other error as a server error', function () {
    $uri = 'http://localhost/broken-' . uniqid();

    logHttpError($uri, new RuntimeException('Broken'));

    expect(loggedErrors($uri))->toBe([['uri' => $uri, 'code' => 500, 'count' => 1]]);
});

it('logs nothing in debug mode', function () {
    $uri = 'http://localhost/debugged-' . uniqid();

    answerTo($uri);

    expect(loggedErrors($uri))->toBe([]);
});

it('does not log a URL that a redirect answers', function () {
    RedirectFactory::createOne(['source' => '/redirected-away', 'target' => '/target']);
    nextRequest();

    answerTo('http://localhost/redirected-away');

    expect(loggedErrors('http://localhost/redirected-away'))->toBe([]);
});

it('offers the path and the site of a logged URL', function () {
    $site = SiteFactory::createOne();
    $uri = 'http://' . $site->getMainDomain() . '/old/' . rawurlencode('Über uns') . '?x=1';
    logHttpError($uri, new NotFoundHttpException());

    $logged = json_decode(Browser::actingAs(UserFactory::new()->admin()->create())
        ->post('/admin/bundle/seo/http-error-log', ['body' => ['filter' => $site->getMainDomain()]])
        ->assertSuccessful()->content(), true)['items'];

    expect($logged)->toHaveCount(1)
        ->and($logged[0])->toMatchArray(['path' => '/old/Über uns', 'siteId' => $site->getId()]);
});

it('removes a URL from the log', function () {
    $uri = 'http://localhost/handled-' . uniqid();
    logHttpError($uri, new NotFoundHttpException());

    Browser::actingAs(UserFactory::new()->admin()->create())
        ->delete('/admin/bundle/seo/http-error-log-entry?' . http_build_query(['uri' => $uri]))
        ->assertSuccessful();

    expect(loggedErrors($uri))->toBe([]);
});
