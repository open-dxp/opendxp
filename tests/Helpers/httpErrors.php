<?php

declare(strict_types=1);

use OpenDxp\Bundle\SeoBundle\EventListener\ResponseExceptionListener;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectTableProvider;
use OpenDxp\Db;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\TestFoundation\Container;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
