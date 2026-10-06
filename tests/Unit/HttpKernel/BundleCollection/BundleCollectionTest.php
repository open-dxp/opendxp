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
use OpenDxp\Tests\Fixtures\Bundle\BundleWithDependency;
use OpenDxp\Tests\Fixtures\Bundle\BundleWithLowPriorityDependency;
use OpenDxp\Tests\Fixtures\Bundle\BundleWithNestedDependencies;
use OpenDxp\Tests\Fixtures\Bundle\CircularBundleOne;
use OpenDxp\Tests\Fixtures\Bundle\CircularBundleTwo;
use OpenDxp\Tests\Fixtures\Bundle\FirstBundle;
use OpenDxp\Tests\Fixtures\Bundle\FourthBundle;
use OpenDxp\Tests\Fixtures\Bundle\RequiredBundle;
use OpenDxp\Tests\Fixtures\Bundle\SecondBundle;
use OpenDxp\Tests\Fixtures\Bundle\ThirdBundle;

beforeEach(fn () => $this->collection = new BundleCollection());

it('adds a bundle instance', function () {
    $bundle = new FirstBundle();

    $this->collection->addBundle($bundle);

    expect($this->collection->getBundles('prod'))->toBe([$bundle]);
});

it('adds a bundle by its class name', function () {
    $this->collection->addBundle(FirstBundle::class);

    expect($this->collection->getIdentifiers())->toBe([FirstBundle::class]);
});

it('adds several bundle instances at once', function () {
    $first = new FirstBundle();
    $second = new SecondBundle();

    $this->collection->addBundles([
        $first,
        $second,
    ]);

    expect($this->collection->getBundles('prod'))->toBe([
        $first,
        $second,
    ]);
});

it('adds several bundles by their class names at once', function () {
    $this->collection->addBundles([
        FirstBundle::class,
        SecondBundle::class,
    ]);

    expect($this->collection->getIdentifiers())->toBe([
        FirstBundle::class,
        SecondBundle::class,
    ]);
});

it('adds an item', function () {
    $item = new Item(new FirstBundle());

    $this->collection->add($item);

    expect($this->collection->getItems())->toBe([$item]);
});

it('does not know a bundle it does not hold', function () {
    $known = $this->collection->hasItem(FirstBundle::class);

    expect($known)->toBeFalse();
});

it('knows a bundle once it is added', function () {
    $this->collection->addBundle(new FirstBundle());

    $known = $this->collection->hasItem(FirstBundle::class);

    expect($known)->toBeTrue();
});

it('returns an item by the class of its bundle', function () {
    $item = new Item(new FirstBundle());
    $this->collection->add($item);

    $found = $this->collection->getItem(FirstBundle::class);

    expect($found)->toBe($item);
});

it('refuses to return an item it does not hold', function () {
    $this->collection->getItem(FirstBundle::class);
})->throws(InvalidArgumentException::class, sprintf('Bundle "%s" is not registered', FirstBundle::class));

it('returns the bundles by priority, the highest first', function () {
    $first = new FirstBundle();
    $second = new SecondBundle();
    $third = new ThirdBundle();
    $fourth = new FourthBundle();
    $this->collection->addBundle($first, 10);
    $this->collection->addBundle($second, 5);
    $this->collection->addBundle($third, -10);
    $this->collection->addBundle($fourth, 50);

    $bundles = $this->collection->getBundles('prod');

    expect($bundles)->toBe([
        $fourth,
        $first,
        $second,
        $third,
    ]);
});

it('lists only the bundles of the environment it is asked for', function (string $environment, array $expected) {
    $this->collection->addBundle(FirstBundle::class);
    $this->collection->addBundle(SecondBundle::class, environments: ['dev']);
    $this->collection->addBundle(
        ThirdBundle::class,
        environments: [
            'dev',
            'test',
        ],
    );
    $this->collection->addBundle(FourthBundle::class, environments: ['test']);

    $identifiers = $this->collection->getIdentifiers($environment);

    expect($identifiers)->toBe($expected);
})->with([
    'production' => [
        'prod',
        [FirstBundle::class],
    ],
    'development' => [
        'dev',
        [
            FirstBundle::class,
            SecondBundle::class,
            ThirdBundle::class,
        ],
    ],
    'test' => [
        'test',
        [
            FirstBundle::class,
            ThirdBundle::class,
            FourthBundle::class,
        ],
    ],
]);

it('registers the bundles a bundle depends on', function () {
    $this->collection->addBundle(new BundleWithDependency());

    expect($this->collection->getIdentifiers())->toBe([
        BundleWithDependency::class,
        RequiredBundle::class,
    ]);
});

it('registers the dependencies of a dependency', function () {
    $this->collection->addBundle(new BundleWithNestedDependencies());

    expect($this->collection->getIdentifiers())->toBe([
        BundleWithNestedDependencies::class,
        FirstBundle::class,
        SecondBundle::class,
        BundleWithDependency::class,
        RequiredBundle::class,
    ]);
});

it('stops at a dependency that points back at the bundle itself', function () {
    $this->collection->addBundle(CircularBundleOne::class, 10);

    expect($this->collection->getIdentifiers())
        ->toBe([
            CircularBundleOne::class,
            CircularBundleTwo::class,
        ])
        ->and($this->collection->getItem(CircularBundleOne::class)->getPriority())
        ->toBe(10)
        ->and($this->collection->getItem(CircularBundleTwo::class)->getPriority())
        ->toBe(8);
});

it('keeps the priority a bundle was added with over a lower one from a dependency', function () {
    $this->collection->addBundle(CircularBundleTwo::class, 50);
    $this->collection->addBundle(CircularBundleOne::class, 10);

    $this->collection->addBundle(new BundleWithLowPriorityDependency());

    expect($this->collection->getIdentifiers())
        ->toBe([
            CircularBundleTwo::class,
            CircularBundleOne::class,
            BundleWithLowPriorityDependency::class,
        ])
        ->and($this->collection->getItem(CircularBundleTwo::class)->getPriority())
        ->toBe(50)
        ->and($this->collection->getItem(CircularBundleOne::class)->getPriority())
        ->toBe(10);
});
