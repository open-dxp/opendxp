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

use InvalidArgumentException;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use OpenDxp\HttpKernel\BundleCollection\Item;
use OpenDxp\Tests\Fixtures\Bundle\BundleA;
use OpenDxp\Tests\Fixtures\Bundle\BundleB;
use OpenDxp\Tests\Fixtures\Bundle\BundleC;
use OpenDxp\Tests\Fixtures\Bundle\BundleD;
use OpenDxp\Tests\Fixtures\Bundle\BundleE;
use OpenDxp\Tests\Fixtures\Bundle\BundleF;
use OpenDxp\Tests\Fixtures\Bundle\BundleG;
use OpenDxp\Tests\Fixtures\Bundle\BundleH;
use OpenDxp\Tests\Fixtures\Bundle\BundleI;
use OpenDxp\Tests\Fixtures\Bundle\BundleJ;
use Symfony\Component\HttpKernel\Bundle\Bundle;

beforeEach(function () {
    $this->collection = new BundleCollection();
    $this->bundles = [new BundleA(), new BundleB(), new BundleC(), new BundleD()];
});

it('takes a bundle instance', function () {

    foreach ($this->bundles as $bundle) {
        $this->collection->addBundle($bundle);
    }

    expect($this->collection->getBundles('prod'))->toBe($this->bundles);
});

it('takes a bundle class name', function () {

    $names = array_map('get_class', $this->bundles);

    foreach ($names as $name) {
        $this->collection->addBundle($name);
    }

    expect($this->collection->getIdentifiers())->toBe($names);
});

it('takes several bundle instances at once', function () {

    $this->collection->addBundles($this->bundles);

    expect($this->collection->getBundles('prod'))->toBe($this->bundles);
});

it('takes several bundle class names at once', function () {

    $names = array_map('get_class', $this->bundles);

    $this->collection->addBundles($names);

    expect($this->collection->getIdentifiers())->toBe($names);
});

it('takes an item', function () {

    foreach ($this->bundles as $bundle) {
        $this->collection->add(new Item($bundle));
    }

    expect($this->collection->getBundles('prod'))->toBe($this->bundles);
});

it('knows an item only once it was added', function () {

    $item = new Item($this->bundles[0]);

    expect($this->collection->hasItem($item->getBundleIdentifier()))->toBeFalse();

    $this->collection->add($item);

    expect($this->collection->hasItem($item->getBundleIdentifier()))->toBeTrue();
});

it('hands an item back by the name of its bundle', function () {

    $item = new Item($this->bundles[0]);
    $this->collection->add($item);

    expect($this->collection->getItem($item->getBundleIdentifier()))->toBe($item);
});

it('refuses to hand back an item it does not hold', function () {
    (new BundleCollection())->getItem(BundleA::class);
})->throws(InvalidArgumentException::class, sprintf('Bundle "%s" is not registered', BundleA::class));

it('hands every item back', function () {

    $items = array_map(static fn (Bundle $bundle) => new Item($bundle), $this->bundles);

    foreach ($items as $item) {
        $this->collection->add($item);
    }

    expect($this->collection->getItems())->toBe($items);
});

it('names every bundle it holds', function () {

    foreach ($this->bundles as $bundle) {
        $this->collection->add(new Item($bundle));
    }

    expect($this->collection->getIdentifiers())->toBe(array_map('get_class', $this->bundles));
});

it('hands the bundles back by priority, the highest first', function () {

    [$a, $b, $c, $d] = $this->bundles;

    $this->collection->addBundle($a, 10);
    $this->collection->addBundle($b, 5);
    $this->collection->addBundle($c, -10);
    $this->collection->addBundle($d, 50);

    expect($this->collection->getBundles('prod'))->toBe([$d, $a, $b, $c]);
});

it('hands back only the bundles of the environment it is asked for', function (string $environment, array $expected) {

    [$always, $dev, $both, $test] = $this->bundles;

    $this->collection->addBundle($always);
    $this->collection->addBundle($dev, 0, ['dev']);
    $this->collection->addBundle($both, 0, ['dev', 'test']);
    $this->collection->addBundle($test, 0, ['test']);

    expect($this->collection->getBundles($environment))
        ->toBe(array_map(fn (int $index) => $this->bundles[$index], $expected));
})->with([
    'production' => ['prod', [0]],
    'development' => ['dev', [0, 1, 2]],
    'test' => ['test', [0, 2, 3]],
]);

it('registers what a bundle depends on', function () {

    $this->collection->addBundle(new BundleE());

    expect($this->collection->getIdentifiers())->toBe([BundleE::class, BundleF::class]);
});

it('registers a dependency of a dependency', function () {

    $this->collection->addBundle(new BundleI());

    expect($this->collection->getIdentifiers())->toBe([
        BundleI::class,
        BundleA::class,
        BundleB::class,
        BundleE::class,
        BundleF::class,
    ]);
});

it('stops at a dependency that points back at the bundle itself', function () {

    $this->collection->addBundle(new BundleG(), 10);

    expect($this->collection->getIdentifiers())
        ->toBe([BundleG::class, BundleH::class])
        ->and($this->collection->getItem(BundleG::class)->getPriority())
        ->toBe(10)
        ->and($this->collection->getItem(BundleH::class)->getPriority())
        ->toBe(8);
});

it('keeps the priority a bundle was added with against a lower one from a dependency', function () {

    $this->collection->addBundle(new BundleH(), 50);
    $this->collection->addBundle(new BundleG(), 10);
    $this->collection->addBundle(new BundleJ());

    expect($this->collection->getIdentifiers())
        ->toBe([BundleH::class, BundleG::class, BundleJ::class])
        ->and($this->collection->getItem(BundleH::class)->getPriority())
        ->toBe(50)
        ->and($this->collection->getItem(BundleG::class)->getPriority())
        ->toBe(10);
});
