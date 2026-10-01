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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Unit\HttpKernel\BundleCollection;

use InvalidArgumentException;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use OpenDxp\HttpKernel\BundleCollection\LazyLoadedItem;
use OpenDxp\Tests\Support\Test\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LazyLoadedItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LazyLoadedItemTestBundleA::resetCounter();
        LazyLoadedItemTestBundleB::resetCounter();
    }

    public function testGetBundle(): void
    {
        $item = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);

        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());

        $bundle = $item->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundleA::getCounter());

        $item->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundleA::getCounter());

        $this->assertInstanceOf(LazyLoadedItemTestBundleA::class, $bundle);
    }

    public function testGetBundleIdentifier(): void
    {
        $item = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);

        $this->assertEquals(LazyLoadedItemTestBundleA::class, $item->getBundleIdentifier());
    }

    public function testExceptionOnInvalidClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "FooBarBazingaDummyClassName" does not exist');
        new LazyLoadedItem('FooBarBazingaDummyClassName');
    }

    public function testisOpenDxpBundle(): void
    {
        $itemA = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);
        $itemB = new LazyLoadedItem(LazyLoadedItemTestBundleB::class);

        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(0, LazyLoadedItemTestBundleB::getCounter());

        $this->assertFalse($itemA->isOpenDxpBundle());
        $this->assertTrue($itemB->isOpenDxpBundle());

        // item is not instantiated
        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(0, LazyLoadedItemTestBundleB::getCounter());
    }

    public function testisOpenDxpBundleWithBundleInstance(): void
    {
        $itemA = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);
        $itemB = new LazyLoadedItem(LazyLoadedItemTestBundleB::class);

        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(0, LazyLoadedItemTestBundleB::getCounter());

        $itemA->getBundle();
        $itemB->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(1, LazyLoadedItemTestBundleB::getCounter());

        $this->assertFalse($itemA->isOpenDxpBundle());
        $this->assertTrue($itemB->isOpenDxpBundle());
    }

    public function testRegistersDependencies(): void
    {
        $collection = new BundleCollection();

        $item = new LazyLoadedItem(LazyLoadedItemTestBundleC::class);

        $collection->add($item);

        $this->assertEquals([
            LazyLoadedItemTestBundleC::class,
            LazyLoadedItemTestBundleA::class,
        ], $collection->getIdentifiers());
    }

    public function testRegistersDependenciesWithBundleInstance(): void
    {
        $collection = new BundleCollection();

        $item = new LazyLoadedItem(LazyLoadedItemTestBundleC::class);
        $item->getBundle();

        $collection->add($item);

        $this->assertEquals([
            LazyLoadedItemTestBundleC::class,
            LazyLoadedItemTestBundleA::class,
        ], $collection->getIdentifiers());
    }
}

class LazyLoadedItemTestBundleA extends Bundle
{
    private static int $counter = 0;

    public function __construct()
    {
        static::$counter++;
    }

    public static function resetCounter(): void
    {
        static::$counter = 0;
    }

    public static function getCounter(): int
    {
        return static::$counter;
    }
}

class LazyLoadedItemTestBundleB extends AbstractOpenDxpBundle
{
    private static int $counter = 0;

    public function __construct()
    {
        static::$counter++;
    }

    public static function resetCounter(): void
    {
        static::$counter = 0;
    }

    public static function getCounter(): int
    {
        return static::$counter;
    }
}

class LazyLoadedItemTestBundleC extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->add(new LazyLoadedItem(LazyLoadedItemTestBundleA::class));
    }
}
