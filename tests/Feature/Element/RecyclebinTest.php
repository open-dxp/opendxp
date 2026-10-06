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
use OpenDxp\Model\Element\ElementInterface;
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

function storedElementOf(Item $item): ElementInterface
{
    $stored = Storage::get('recycle_bin')->read($item->getStorageFile());

    return unserialize($stored);
}

function recycleBinRoot(): string
{
    return sprintf('%s/var/recyclebin', Container::parameter('kernel.project_dir'));
}

beforeEach(function () {
    $this->user = UserFactory::new()
        ->admin()
        ->create();
});

it('stores an element it takes in', function () {
    $object = UnittestFactory::createOne();

    Item::create($object, $this->user);

    $item = recycled($object->getFullPath());
    expect(storedElementOf($item))->getId()->toBe($object->getId());
});

it('restores a deleted element', function () {
    $object = UnittestFactory::createOne();
    Item::create($object, $this->user);
    $object->delete();

    recycled($object->getFullPath())->restore();

    expect(DataObject::getById($object->getId()))->toBeInstanceOf($object::class);
});

it('stores the children of an element it takes in', function () {
    $parent = UnittestFactory::createOne();
    UnittestFactory::new()
        ->withParent($parent)
        ->create();

    Item::create($parent, $this->user);

    $item = recycled($parent->getFullPath());
    expect($item->getAmount())
        ->toBe(2)
        ->and(storedElementOf($item)->getChildren(includingUnpublished: true))
        ->toHaveCount(1);
});

it('restores the children of a deleted element', function () {
    $parent = UnittestFactory::createOne();
    $child = UnittestFactory::new()
        ->withParent($parent)
        ->create();
    Item::create($parent, $this->user);
    $parent->delete();

    recycled($parent->getFullPath())->restore();

    expect(DataObject::getById($child->getId()))->toBeInstanceOf($child::class);
});

it('restores the data and the relations of a deleted element', function () {
    $related = UnittestFactory::createOne();
    $object = UnittestFactory::createOne([
        'input' => 'some input',
        'objects' => [$related],
        'lobjects' => [$related],
    ]);
    Item::create($object, $this->user);
    $object->delete();

    recycled($object->getFullPath())->restore();

    $restored = DataObject::getById($object->getId());
    expect($restored->getInput())
        ->toBe('some input')
        ->and($restored->getObjects()[0]->getId())
        ->toBe($related->getId())
        ->and($restored->getLobjects()[0]->getId())
        ->toBe($related->getId());
});

it('empties itself when its storage root is a symlink', function () {
    $root = recycleBinRoot();
    $target = sprintf('%s-moved', $root);
    rename($root, $target);
    symlink($target, $root);
    $object = UnittestFactory::createOne();
    Item::create($object, $this->user);
    $object->delete();

    (new Recyclebin())->flush();

    $contents = Storage::get('recycle_bin')->listContents('/', deep: true);
    expect(iterator_to_array($contents, preserve_keys: false))
        ->toBeEmpty()
        ->and(is_dir($root))
        ->toBeTrue();
})->after(function () {
    $root = recycleBinRoot();
    unlink($root);
    rename(
        sprintf('%s-moved', $root),
        $root,
    );
});
