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

namespace OpenDxp\Event;

final class HttpCacheEvents
{
    /**
     * Fired in HttpCache::collectTagsFor() before tags are added
     * to the response collector. Listeners can call cancel() to prevent the
     * tags from being tracked. To add tags, register a strategy instead.
     *
     * @Event("OpenDxp\Event\HttpCache\HttpCacheTagGuardEvent")
     */
    public const string TAG_GUARD = 'opendxp.httpCache.tagGuard';

    /**
     * Fired in HttpCache::invalidate() with the tags a change invalidates, also without a proxy client.
     *
     * @Event("OpenDxp\Event\HttpCache\HttpCacheInvalidateEvent")
     */
    public const string INVALIDATE = 'opendxp.httpCache.invalidate';
}
