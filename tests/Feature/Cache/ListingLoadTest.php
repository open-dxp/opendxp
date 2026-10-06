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

namespace OpenDxp\Tests\Feature\Cache;

use OpenDxp;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * @return list<int>
 */
function idsLoadedBy(callable $load): array
{
    $loaded = [];
    $listener = static function (DataObjectEvent $event) use (&$loaded): void {
        $loaded[] = $event->getObject()->getId();
    };
    $dispatcher = OpenDxp::getEventDispatcher();
    $dispatcher->addListener(DataObjectEvents::POST_LOAD, $listener);

    try {
        $load();
    } finally {
        $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $listener);
    }

    return $loaded;
}

dataset('cached objects', [
    'every object' => [
        fn (array $objects) => $objects,
    ],
    'some of the objects' => [
        fn (array $objects) => [
            $objects[0],
            $objects[2],
        ],
    ],
]);

beforeEach(fn () => useApplicationCache());

it('keeps the order of the listing for objects from the cache', function (callable $cached) {
    $objects = UnittestFactory::createMany(
        4,
        fn (int $index) => ['input' => sprintf('listed_%d', $index)],
    );
    foreach ($cached($objects) as $object) {
        cacheAsAnEarlierRequest($object);
    }
    RuntimeCache::clear();

    $loaded = unittestListing('listed_')->load();

    expect(elementIds($loaded))->toBe(elementIds($objects));
})->with('cached objects');

it('fires the post load event once per object in the order of the listing', function (callable $cached) {
    $objects = UnittestFactory::createMany(
        4,
        fn (int $index) => ['input' => sprintf('listed_%d', $index)],
    );
    foreach ($cached($objects) as $object) {
        cacheAsAnEarlierRequest($object);
    }
    RuntimeCache::clear();

    $fired = idsLoadedBy(fn () => unittestListing('listed_')->load());

    expect($fired)->toBe(elementIds($objects));
})->with('cached objects');
