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
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('returns nothing for an id that no element has', function (string $element, int $id) {
    $loaded = $element::getById($id);

    expect($loaded)->toBeNull();
})->with([
    'an asset' => [Asset::class],
    'a document' => [Document::class],
    'an object' => [AbstractObject::class],
])->with([
    'zero' => [0],
    'a negative id' => [-5],
    'an id nothing was ever given' => [99999999],
]);

it('loads an element as the class it was saved as', function (string $element, string $factory) {
    $saved = $factory::createOne();
    RuntimeCache::clear();

    $loaded = $element::getById($saved->getId());

    expect($loaded)->toBeInstanceOf($factory::class());
})->with([
    'an asset' => [Asset::class, AssetImageFactory::class],
    'an asset folder' => [Asset::class, AssetFolderFactory::class],
    'a document' => [Document::class, DocumentPageFactory::class],
    'a document folder' => [Document::class, DocumentFolderFactory::class],
    'an object' => [AbstractObject::class, UnittestFactory::class],
    'an object folder' => [AbstractObject::class, DataObjectFolderFactory::class],
]);

it('loads an element through its own class', function (string $element, string $factory) {
    $saved = $factory::createOne();
    $class = $factory::class();
    RuntimeCache::clear();

    $loaded = $class::getById($saved->getId());

    expect($loaded)->getId()->toBe($saved->getId());
})->with('elements');

it('returns nothing for an element of another class', function (string $class, string $factory) {
    $saved = $factory::createOne();
    RuntimeCache::clear();

    $loaded = $class::getById($saved->getId());

    expect($loaded)->toBeNull();
})->with([
    'an asset folder asked for as an image' => [Asset\Image::class, AssetFolderFactory::class],
    'a document folder asked for as a page' => [Document\Page::class, DocumentFolderFactory::class],
    'an object folder asked for as an object' => [Concrete::class, DataObjectFolderFactory::class],
]);

it('loads the stored values of an element', function (string $element, string $factory) {
    $saved = $factory::createOne();
    RuntimeCache::clear();

    $loaded = $element::getById($saved->getId());

    expect($loaded)
        ->getKey()
        ->toBe($saved->getKey())
        ->getParentId()
        ->toBe($saved->getParentId())
        ->getModificationDate()
        ->toBe($saved->getModificationDate());
})->with('elements');

it('fires the post load event', function (string $element, string $factory, string $event) {
    $saved = $factory::createOne();
    RuntimeCache::clear();
    $loadedIds = [];
    // Every test boots a kernel of its own, so the listener ends with the test.
    OpenDxp::getEventDispatcher()->addListener(
        $event,
        static function (ElementEventInterface $fired) use (&$loadedIds): void {
            $loadedIds[] = $fired->getElement()->getId();
        },
    );

    $element::getById($saved->getId());

    expect($loadedIds)->toBe([$saved->getId()]);
})->with([
    'an asset' => [Asset::class, AssetImageFactory::class, AssetEvents::POST_LOAD],
    'a document' => [Document::class, DocumentPageFactory::class, DocumentEvents::POST_LOAD],
    'an object' => [AbstractObject::class, UnittestFactory::class, DataObjectEvents::POST_LOAD],
]);

it('returns the instance it already holds on a second load', function (string $element, string $factory) {
    $saved = $factory::createOne();
    RuntimeCache::clear();
    $first = $element::getById($saved->getId());

    $second = $element::getById($saved->getId());

    expect($second)->toBe($first);
})->with('elements');

it('builds a fresh instance of the same class when the load is forced', function (string $element, string $factory) {
    $saved = $factory::createOne();
    RuntimeCache::clear();
    $held = $element::getById($saved->getId());

    $forced = $element::getById(
        $saved->getId(),
        ['force' => true],
    );

    expect($forced)
        ->not->toBe($held)
        ->toBeInstanceOf($factory::class())
        ->getId()
        ->toBe($saved->getId());
})->with('elements');
