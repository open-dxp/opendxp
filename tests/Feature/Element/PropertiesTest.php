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

use OpenDxp\Model\Asset;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Tests\Factory\InheritanceFactory;

dataset('property holders', [
    'an asset folder' => AssetFolderFactory::class,
    'a document folder' => DocumentFolderFactory::class,
    'an object' => InheritanceFactory::class,
]);

it('stores every property it is given', function (string $factory) {
    $holder = $factory::new()
        ->withProperty('first', 'input', 'one')
        ->withProperty('second', 'input', 'two')
        ->create();

    expect(reloaded($holder))
        ->getProperty('first')
        ->toBe('one')
        ->getProperty('second')
        ->toBe('two');
})->with('property holders');

it('replaces a property that is set again', function (string $factory) {
    $holder = $factory::new()
        ->withProperty('first', 'input', 'one')
        ->create();
    $holder->setProperty('first', 'input', 'two');

    $holder->save();

    expect(reloaded($holder)->getProperty('first'))->toBe('two');
})->with('property holders');

it('empties a property that is set to null and keeps the others', function (string $factory) {
    $holder = $factory::new()
        ->withProperty('first', 'input', 'one')
        ->withProperty('second', 'input', 'two')
        ->create();
    $holder->setProperty('first', 'input', null);

    $holder->save();

    expect(reloaded($holder))
        ->getProperty('first')
        ->toBeNull()
        ->getProperty('second')
        ->toBe('two');
})->with('property holders');

it('passes an inheritable property down to a child', function (string $factory) {
    $parent = $factory::new()
        ->withInheritableProperty('inherited', 'input', 'one')
        ->create();

    $child = $factory::new()
        ->withParent($parent)
        ->create();

    expect(reloaded($child)->getProperty('inherited'))->toBe('one');
})->with('property holders');

it('returns an element property as the element it points at', function (string $factory) {
    $image = AssetImageFactory::createOne();

    $holder = $factory::new()
        ->withProperty('image', 'asset', $image)
        ->create();

    expect(reloaded($holder)->getProperty('image'))
        ->toBeInstanceOf(Asset::class)
        ->getId()
        ->toBe($image->getId());
})->with('property holders');
