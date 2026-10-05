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
use OpenDxp\HttpKernel\BundleCollection\LazyLoadedItem;
use OpenDxp\Tests\Fixtures\Bundle\BundleE;
use OpenDxp\Tests\Fixtures\Bundle\BundleF;
use OpenDxp\Tests\Fixtures\Bundle\CountingBundle;
use OpenDxp\Tests\Fixtures\Bundle\CountingOpenDxpBundle;

beforeEach(function () {
    CountingBundle::forgetInstances();
    CountingOpenDxpBundle::forgetInstances();
});

it('builds its bundle on the first ask and keeps it', function () {

    $item = new LazyLoadedItem(CountingBundle::class);

    expect(CountingBundle::instances())->toBe(0);

    $bundle = $item->getBundle();
    $item->getBundle();

    expect($bundle)
        ->toBeInstanceOf(CountingBundle::class)
        ->and(CountingBundle::instances())
        ->toBe(1);
});

it('is named after the class it was given', function () {
    expect((new LazyLoadedItem(CountingBundle::class))->getBundleIdentifier())->toBe(CountingBundle::class);
});

it('refuses a class that does not exist', function () {
    new LazyLoadedItem('FooBarBazingaDummyClassName');
})->throws(InvalidArgumentException::class, 'The class "FooBarBazingaDummyClassName" does not exist');

it('says whether its bundle is an opendxp bundle without building it', function () {

    $plain = new LazyLoadedItem(CountingBundle::class);
    $openDxp = new LazyLoadedItem(CountingOpenDxpBundle::class);

    expect($plain->isOpenDxpBundle())
        ->toBeFalse()
        ->and($openDxp->isOpenDxpBundle())
        ->toBeTrue()
        ->and(CountingBundle::instances())
        ->toBe(0)
        ->and(CountingOpenDxpBundle::instances())
        ->toBe(0);
});

it('says the same once its bundle is built', function () {

    $plain = new LazyLoadedItem(CountingBundle::class);
    $openDxp = new LazyLoadedItem(CountingOpenDxpBundle::class);

    $plain->getBundle();
    $openDxp->getBundle();

    expect($plain->isOpenDxpBundle())
        ->toBeFalse()
        ->and($openDxp->isOpenDxpBundle())
        ->toBeTrue()
        ->and(CountingBundle::instances())
        ->toBe(1)
        ->and(CountingOpenDxpBundle::instances())
        ->toBe(1);
});

it('brings the bundles its own depends on', function (bool $buildFirst) {

    $collection = new BundleCollection();
    $item = new LazyLoadedItem(BundleE::class);

    if ($buildFirst) {
        $item->getBundle();
    }

    $collection->add($item);

    expect($collection->getIdentifiers())->toBe([BundleE::class, BundleF::class]);
})->with([
    'while it is still unbuilt' => [false],
    'once it is built' => [true],
]);
