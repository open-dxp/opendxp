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

use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element\Service;
use OpenDxp\Tests\Factory\RelationTestFactory;

/**
 * Reads the object back the way the next request would, out of the persistent cache.
 */
function fromTheCache(Concrete $object): Concrete
{
    $enabled = Cache::isEnabled();
    Cache::enable();
    Cache::getHandler()->setHandleCli(true);

    Cache::getHandler()->removeClearedTags($object->getCacheTags());
    Cache::save($object, Service::getElementCacheTag('object', $object->getId()), [], null, 9999, true);
    RuntimeCache::clear();

    $cached = Concrete::getById($object->getId());

    if (!$enabled) {
        Cache::disable();
        Cache::getHandler()->setHandleCli(false);
    }

    return $cached;
}

/**
 * The internals a lazy loaded field keeps, which must never end up in a serialized object.
 */
function lazyLoadingInternals(): array
{
    return ['lazyLoadedFields', 'lazyKeys', 'loadedLazyKeys'];
}

function relationContent(): string
{
    return RelationTestFactory::CONTENT;
}
