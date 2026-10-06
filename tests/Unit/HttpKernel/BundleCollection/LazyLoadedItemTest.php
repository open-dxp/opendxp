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

namespace OpenDxp\Tests\Unit\HttpKernel\BundleCollection;

use Closure;
use InvalidArgumentException;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use OpenDxp\HttpKernel\BundleCollection\LazyLoadedItem;
use OpenDxp\Tests\Fixtures\Bundle\BundleWithDependency;
use OpenDxp\Tests\Fixtures\Bundle\CountingBundle;
use OpenDxp\Tests\Fixtures\Bundle\CountingOpenDxpBundle;
use OpenDxp\Tests\Fixtures\Bundle\RequiredBundle;

dataset('bundle kinds', [
    'a plain bundle' => [CountingBundle::class, false],
    'an OpenDXP bundle' => [CountingOpenDxpBundle::class, true],
]);

afterEach(function () {
    CountingBundle::forgetInstances();
    CountingOpenDxpBundle::forgetInstances();
});

it('builds no bundle when it is created', function () {
    new LazyLoadedItem(CountingBundle::class);

    expect(CountingBundle::instances())->toBe(0);
});

it('builds its bundle on first access', function () {
    $item = new LazyLoadedItem(CountingBundle::class);

    $bundle = $item->getBundle();

    expect($bundle)
        ->toBeInstanceOf(CountingBundle::class)
        ->and(CountingBundle::instances())
        ->toBe(1);
});

it('reuses its bundle on later access', function () {
    $item = new LazyLoadedItem(CountingBundle::class);
    $first = $item->getBundle();

    $second = $item->getBundle();

    expect($second)
        ->toBe($first)
        ->and(CountingBundle::instances())
        ->toBe(1);
});

it('is named after the class it was given', function () {
    $item = new LazyLoadedItem(CountingBundle::class);

    expect($item)->getBundleIdentifier()->toBe(CountingBundle::class);
});

it('refuses a class that does not exist', function () {
    new LazyLoadedItem('FooBarBazingaDummyClassName');
})->throws(InvalidArgumentException::class, 'The class "FooBarBazingaDummyClassName" does not exist');

it('tells whether its bundle is an OpenDXP bundle', function (string $className, bool $openDxpBundle) {
    $item = new LazyLoadedItem($className);

    $result = $item->isOpenDxpBundle();

    expect($result)->toBe($openDxpBundle);
})->with('bundle kinds');

it('tells whether its bundle is an OpenDXP bundle without building it', function () {
    $item = new LazyLoadedItem(CountingOpenDxpBundle::class);

    $item->isOpenDxpBundle();

    expect(CountingOpenDxpBundle::instances())->toBe(0);
});

it('tells whether its built bundle is an OpenDXP bundle', function (string $className, bool $openDxpBundle) {
    $item = new LazyLoadedItem($className);
    $item->getBundle();

    $result = $item->isOpenDxpBundle();

    expect($result)->toBe($openDxpBundle);
})->with('bundle kinds');

it('registers the bundles its bundle depends on', function (Closure $prepare) {
    $collection = new BundleCollection();
    $item = new LazyLoadedItem(BundleWithDependency::class);
    $prepare($item);

    $collection->add($item);

    expect($collection->getIdentifiers())->toBe([
        BundleWithDependency::class,
        RequiredBundle::class,
    ]);
})->with([
    'while its bundle is not built' => fn (LazyLoadedItem $item) => null,
    'once its bundle is built' => fn (LazyLoadedItem $item) => $item->getBundle(),
]);
