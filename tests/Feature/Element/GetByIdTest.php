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

namespace OpenDxp\Tests\Feature\Element;

use OpenDxp;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\ElementEventInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('hands back nothing for an id that no element has', function (string $element, int $id) {
    expect($element::getById($id))->toBeNull();
})->with([
    'an asset' => [Asset::class],
    'a document' => [Document::class],
    'an object' => [AbstractObject::class],
])->with([
    'zero' => [0],
    'a negative id' => [-5],
    'an id nothing was ever given' => [99999999],
]);

it('loads an element of the class it was saved as', function (string $element, string $factory) {

    $saved = $factory::createOne();
    RuntimeCache::clear();

    $loaded = $element::getById($saved->getId());

    expect($loaded)
        ->toBeInstanceOf($factory::class())
        ->and($loaded->getId())
        ->toBe($saved->getId())
        ->and($loaded->getKey())
        ->toBe($saved->getKey());
})->with('elements');

it('fires its post load event for an element it loaded', function (string $element, string $factory, string $event) {

    $saved = $factory::createOne();
    RuntimeCache::clear();

    $loaded = [];
    $listener = static function (ElementEventInterface $fired) use (&$loaded): void {
        $loaded[] = $fired->getElement()->getId();
    };

    OpenDxp::getEventDispatcher()->addListener($event, $listener);

    try {
        $element::getById($saved->getId());
    } finally {
        OpenDxp::getEventDispatcher()->removeListener($event, $listener);
    }

    expect($loaded)->toContain($saved->getId());
})->with([
    'an asset' => [Asset::class, AssetImageFactory::class, AssetEvents::POST_LOAD],
    'a document' => [Document::class, DocumentPageFactory::class, DocumentEvents::POST_LOAD],
    'an object' => [AbstractObject::class, UnittestFactory::class, DataObjectEvents::POST_LOAD],
]);

it('hands back the instance it already holds on a second load', function (string $element, string $factory) {

    $saved = $factory::createOne();
    RuntimeCache::clear();

    expect($element::getById($saved->getId()))->toBe($element::getById($saved->getId()));
})->with('elements');

it('builds a fresh instance when the load is forced', function (string $element, string $factory) {

    $saved = $factory::createOne();
    RuntimeCache::clear();

    $cached = $element::getById($saved->getId());
    $forced = $element::getById($saved->getId(), ['force' => true]);

    expect($forced)
        ->not->toBe($cached)
        ->and($forced->getId())
        ->toBe($saved->getId());
})->with('elements');
