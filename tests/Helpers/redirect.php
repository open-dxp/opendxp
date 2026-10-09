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

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\CachedRedirects;
use OpenDxp\Cache;
use OpenDxp\Db;

function recordHits(Redirect $redirect, int $hits, int $lastHit): void
{
    Db::get()->insert('redirect_hits', [
        'redirectId' => $redirect->getId(),
        'hits' => $hits,
        'lastHit' => $lastHit,
    ]);
}

/**
 * Caches the redirects as a request finds them while the SEO bundle is not installed.
 */
function cacheRedirectsAsNotInstalled(): void
{
    Cache::getHandler()->removeClearedTags(['redirect']);
    Cache::save(
        CachedRedirects::notInstalled(),
        'system_route_redirect',
        ['system', 'redirect', 'route'],
        force: true,
    );
    resetServices();
}
