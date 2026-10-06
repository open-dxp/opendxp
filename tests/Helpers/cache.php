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
use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * A test runs on the command line, where a handler writes nothing unless it is told to handle the command line.
 * This handler is told to, so it writes as it does in a web request.
 */
function cacheHandler(TagAwareAdapterInterface $pool): CoreCacheHandler
{
    $lock = new WriteLock($pool);
    $lock->setLogger(new NullLogger());

    $handler = new CoreCacheHandler($pool, $lock, new EventDispatcher());
    $handler->setLogger(new NullLogger());
    $handler->setHandleCli(true);

    return $handler;
}

/**
 * The test application keeps the cache of OpenDXP off. A new kernel turns it off again for the next test.
 */
function useApplicationCache(): void
{
    Cache::enable();
    Cache::getHandler()->setHandleCli(true);
    RuntimeCache::clear();
}

/**
 * Saving an element clears its cache tag for the whole process. Without taking that back, the element cannot be
 * cached in the same test.
 */
function allowCachingAgain(ElementInterface $element): void
{
    $tags = array_values($element->getCacheTags());
    Cache::getHandler()->removeClearedTags($tags);
}

/**
 * Writes the element into the cache of OpenDXP the way an earlier request would, and returns its cache key.
 */
function cacheAsAnEarlierRequest(ElementInterface $element): string
{
    allowCachingAgain($element);

    $key = Service::getElementCacheTag(
        Service::getElementType($element),
        $element->getId(),
    );
    Cache::save($element, $key, force: true);

    return $key;
}

/**
 * Reads the object back the way the next request would, out of the cache of OpenDXP.
 *
 * @template T of Concrete
 *
 * @param T $object
 *
 * @return T
 */
function cachedCopyOf(Concrete $object): Concrete
{
    useApplicationCache();
    cacheAsAnEarlierRequest($object);
    RuntimeCache::clear();

    return $object::getById($object->getId());
}
