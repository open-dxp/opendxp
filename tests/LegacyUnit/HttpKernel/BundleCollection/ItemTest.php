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

use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use OpenDxp\HttpKernel\BundleCollection\Item;
use OpenDxp\Tests\Support\Test\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ItemTest extends TestCase
{
    public function testGetBundle(): void
    {
        $bundle = new ItemTestBundleA();
        $item = new Item(new ItemTestBundleA());

        $this->assertEquals($bundle, $item->getBundle());
    }

    public function testGetBundleIdentifier(): void
    {
        $item = new Item(new ItemTestBundleA());

        $this->assertEquals(ItemTestBundleA::class, $item->getBundleIdentifier());
    }

    public function testEmptyEnvironmentsMatchesAnyEnvironment(): void
    {
        $item = new Item(new ItemTestBundleA(), 0, []);
        foreach (['prod', 'dev', 'test'] as $environment) {
            $this->assertTrue($item->matchesEnvironment($environment));
        }
    }

    public function testItemMatchesEnvironment(): void
    {
        $item = new Item(new ItemTestBundleA(), 0, ['dev']);

        $this->assertTrue($item->matchesEnvironment('dev'));
        $this->assertFalse($item->matchesEnvironment('prod'));
        $this->assertFalse($item->matchesEnvironment('test'));
    }

    public function testItemWithMultipleEnvironments(): void
    {
        $item = new Item(new ItemTestBundleA(), 0, ['dev', 'test']);

        $this->assertTrue($item->matchesEnvironment('dev'));
        $this->assertTrue($item->matchesEnvironment('test'));
        $this->assertFalse($item->matchesEnvironment('prod'));
    }

    public function testisOpenDxpBundle(): void
    {
        $itemA = new Item(new ItemTestBundleA());
        $itemB = new Item(new ItemTestBundleB());

        $this->assertFalse($itemA->isOpenDxpBundle());
        $this->assertTrue($itemB->isOpenDxpBundle());
    }

    public function testRegistersDependencies(): void
    {
        $collection = new BundleCollection();

        $collection->add(new Item(new ItemTestBundleC()));

        $this->assertEquals([
            ItemTestBundleC::class,
            ItemTestBundleA::class,
        ], $collection->getIdentifiers());
    }
}

class ItemTestBundleA extends Bundle
{
}

class ItemTestBundleB extends AbstractOpenDxpBundle
{
}

class ItemTestBundleC extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->add(new Item(new ItemTestBundleA()));
    }
}
