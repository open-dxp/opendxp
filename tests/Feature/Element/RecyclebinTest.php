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

use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element\Recyclebin;
use OpenDxp\Model\Element\Recyclebin\Item;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;
use OpenDxp\Tool\Storage;

function recycled(string $path): Item
{
    $listing = new Item\Listing();
    $listing->setCondition('`path` = ?', $path);

    return $listing->current();
}

beforeEach(fn () => $this->user = UserFactory::new()->admin()->create());

it('keeps the element it took in and hands it back on a restore', function () {

    $object = UnittestFactory::createOne();
    $path = $object->getFullPath();

    Item::create($object, $this->user);
    $object->delete();

    $item = recycled($path);
    $storage = Storage::get('recycle_bin');

    expect($storage->fileExists($item->getStorageFile()))
        ->toBeTrue()
        ->and(unserialize($storage->read($item->getStorageFile()))->getId())
        ->toBe($object->getId());

    $item->restore();

    expect(DataObject::getById($object->getId()))->toBeInstanceOf($object::class);
});

it('takes in everything below the element and hands all of it back', function () {

    $parent = UnittestFactory::createOne();
    $child = UnittestFactory::createOne(['parentId' => $parent->getId()]);
    $path = $parent->getFullPath();

    Item::create($parent, $this->user);
    $parent->delete();

    $item = recycled($path);
    $stored = unserialize(Storage::get('recycle_bin')->read($item->getStorageFile()));

    expect($item->getAmount())
        ->toBe(2)
        ->and($stored->getId())
        ->toBe($parent->getId())
        ->and($stored->getChildren(DataObject::$types, true)->getData())
        ->toHaveCount(1);

    $item->restore();

    expect(DataObject::getById($parent->getId()))
        ->toBeInstanceOf($parent::class)
        ->and(DataObject::getById($child->getId()))
        ->toBeInstanceOf($child::class);
});

it('hands back the data and the relations of a restored element', function () {

    $related = UnittestFactory::createOne();
    $object = UnittestFactory::createOne([
        'input' => 'some input',
        'objects' => [$related],
        'lobjects' => [$related],
    ]);
    $path = $object->getFullPath();

    Item::create($object, $this->user);
    $object->delete();

    recycled($path)->restore();
    $restored = DataObject::getById($object->getId());

    expect($restored->getInput())
        ->toBe('some input')
        ->and($restored->getObjects()[0]->getId())
        ->toBe($related->getId())
        ->and($restored->getLobjects()[0]->getId())
        ->toBe($related->getId());
});

it('empties itself even when its storage root is a symlink', function () {

    $root = Container::parameter('kernel.project_dir') . '/var/recyclebin';
    $target = $root . '-moved';

    rename($root, $target);
    symlink($target, $root);

    try {
        $object = UnittestFactory::createOne();
        Item::create($object, $this->user);
        $object->delete();

        $storage = Storage::get('recycle_bin');

        expect(iterator_to_array($storage->listContents('/', true), false))->not->toBeEmpty();

        (new Recyclebin())->flush();

        expect(iterator_to_array($storage->listContents('/', true), false))
            ->toBeEmpty()
            ->and(is_dir($root))
            ->toBeTrue();
    } finally {
        unlink($root);
        rename($target, $root);
    }
});
