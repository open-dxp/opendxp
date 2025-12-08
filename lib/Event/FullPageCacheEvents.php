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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Event;

final class FullPageCacheEvents
{
    /**
     * Fired when the full page chage determines if it should disable
     * the cache due to existing session data. Keys handled in this
     * event will be ignored when checking if the session has any data.
     *
     * @Event("OpenDxp\Event\Cache\FullPage\IgnoredSessionKeysEvent")
     */
    const string IGNORED_SESSION_KEYS = 'opendxp.cache.full_page.ignored_session_keys';

    /**
     * Fired to determine if a response should be cached.
     *
     * @Event("OpenDxp\Event\Cache\FullPage\CacheResponseEvent")
     */
    const string CACHE_RESPONSE = 'opendxp.cache.full_page.cache_response';

    /**
     * Fired before the response is written to cache. Can be used to set or purge
     * data on the cached response.
     *
     * @Event("OpenDxp\Event\Cache\FullPage\PrepareResponseEvent")
     */
    const string PREPARE_RESPONSE = 'opendxp.cache.full_page.prepare_response';

    /**
     * Fired before the response is written to cache. Can be used to add tags
     * to the cached response.
     *
     * @Event("OpenDxp\Event\Cache\FullPage\PrepareTagsEvent")
     */
    const string PREPARE_TAGS = 'opendxp.cache.full_page.prepare_tags';
}
