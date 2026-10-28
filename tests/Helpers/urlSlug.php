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

use OpenDxp\Event\UrlSlugEvents;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Model\DataObject\Data\UrlSlug;
use OpenDxp\Model\Site;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Application\Service\AvailableSitesListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @param UrlSlug[]|null $slugs
 *
 * @return array<int, string>
 */
function slugsBySite(?array $slugs): array
{
    $bySite = [];

    foreach ($slugs ?? [] as $slug) {
        $bySite[$slug->getSiteId() ?? 0] = $slug->getSlug();
    }

    ksort($bySite);

    return $bySite;
}

function runWithAvailableSitesListener(AvailableSitesListener $listener, Closure $action): mixed
{
    $dispatcher = OpenDxp::getEventDispatcher();
    $dispatcher->addListener(UrlSlugEvents::AVAILABLE_SITES, $listener);

    try {
        return $action();
    } finally {
        $dispatcher->removeListener(UrlSlugEvents::AVAILABLE_SITES, $listener);
    }
}

function findRoutedObjectId(string $path, ?Site $site = null): ?int
{
    $request = Request::create($path);

    if ($site instanceof Site) {
        $request->attributes->set(SiteResolver::ATTRIBUTE_SITE, $site);
    }

    try {
        return (Container::get('router')->matchRequest($request)['object'] ?? null)?->getId();
    } catch (ResourceNotFoundException) {
        return null;
    }
}
