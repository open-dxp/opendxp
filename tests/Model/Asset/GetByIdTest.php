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

namespace OpenDxp\Tests\Model\Asset;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Model\Asset;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;

/**
 * Pins down {@see Asset::getById()} behavior so the proposed `hasListeners()`
 * short-circuit on POST_LOAD events cannot drop the dispatch when listeners
 * actually exist.
 *
 * @group model.asset.getbyid
 */
class GetByIdTest extends ModelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        TestHelper::cleanUp();
        RuntimeCache::clear();
        parent::tearDown();
    }

    public function testReturnsNullForInvalidId(): void
    {
        $this->assertNull(Asset::getById(0));
        $this->assertNull(Asset::getById(99999999));
    }

    public function testGetByIdLoadsAsset(): void
    {
        $asset = TestHelper::createImageAsset();
        $id = $asset->getId();

        RuntimeCache::clear();

        $loaded = Asset::getById($id);
        $this->assertInstanceOf(Asset::class, $loaded);
        $this->assertSame($id, $loaded->getId());
    }

    public function testPostLoadEventFiresForAsset(): void
    {
        $asset = TestHelper::createImageAsset();
        $id = $asset->getId();

        RuntimeCache::clear();

        $dispatcher = \OpenDxp::getEventDispatcher();
        $captured = [];
        $listener = function (AssetEvent $event) use (&$captured): void {
            $captured[] = $event->getAsset()->getId();
        };
        $dispatcher->addListener(AssetEvents::POST_LOAD, $listener);

        try {
            Asset::getById($id);
        } finally {
            $dispatcher->removeListener(AssetEvents::POST_LOAD, $listener);
        }

        $this->assertContains($id, $captured, 'POST_LOAD must fire when loading an asset');
    }

    public function testGetByIdSucceedsWithoutAnyPostLoadListener(): void
    {
        $asset = TestHelper::createImageAsset();
        $id = $asset->getId();

        RuntimeCache::clear();

        $loaded = Asset::getById($id);
        $this->assertInstanceOf(Asset::class, $loaded);
    }
}
