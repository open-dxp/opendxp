<?php

declare(strict_types=1);

use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectTableProvider;
use OpenDxp\Cache;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;

/**
 * Sends the request without following a redirect.
 *
 * @return array{status: int, location: ?string, redirect: ?int}
 */
function answerTo(string $uri): array
{
    $response = Browser::start()->interceptRedirects()->visit($uri)->client()->getResponse();
    $redirectId = $response->headers->get(RedirectHandler::RESPONSE_HEADER_NAME_ID);

    return [
        'status' => $response->getStatusCode(),
        'location' => $response->headers->get('Location'),
        'redirect' => $redirectId === null ? null : (int) $redirectId,
    ];
}

/**
 * Saving a redirect clears the cache tag "redirect", and the core cache refuses that tag for the rest of the request.
 * A test plays the next request by allowing the tag again.
 */
function nextRequest(): void
{
    Cache::getHandler()->removeClearedTags(['redirect']);
    Container::get(RedirectTableProvider::class)->reset();
}
