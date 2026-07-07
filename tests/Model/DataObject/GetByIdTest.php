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

namespace OpenDxp\Tests\Model\DataObject;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Folder;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Pins down the behavior of {@see AbstractObject::getById()} so that the
 * planned merge of the type-discriminator and full-row queries cannot change
 * what callers see.
 *
 * @group model.dataobject.getbyid
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
        $this->assertNull(DataObject::getById(0));
        $this->assertNull(DataObject::getById(-1));
    }

    public function testReturnsNullForNonExistentId(): void
    {
        $this->assertNull(DataObject::getById(99999999));
    }

    public function testFolderIsLoadedAsFolderInstance(): void
    {
        $folder = TestHelper::createObjectFolder('getbyid-folder-');
        $id = $folder->getId();

        RuntimeCache::clear();

        $loaded = DataObject::getById($id);

        $this->assertInstanceOf(Folder::class, $loaded);
        $this->assertSame($id, $loaded->getId());
        $this->assertSame($folder->getKey(), $loaded->getKey());
    }

    public function testConcreteObjectIsLoadedAsCorrectSubclass(): void
    {
        $object = TestHelper::createEmptyObject('getbyid-object-');
        $id = $object->getId();

        RuntimeCache::clear();

        $loaded = DataObject::getById($id);

        $this->assertInstanceOf(Unittest::class, $loaded);
        $this->assertSame($id, $loaded->getId());
        $this->assertSame($object->getKey(), $loaded->getKey());
    }

    public function testGetByIdLoadsAllPropertiesFromObjectsTable(): void
    {
        $object = TestHelper::createEmptyObject('getbyid-fields-');
        $id = $object->getId();
        $expectedKey = $object->getKey();
        $expectedParentId = $object->getParentId();
        $expectedPublished = $object->getPublished();
        $expectedModificationDate = $object->getModificationDate();

        RuntimeCache::clear();

        $loaded = DataObject::getById($id);

        $this->assertNotNull($loaded);
        $this->assertSame($id, $loaded->getId());
        $this->assertSame($expectedKey, $loaded->getKey());
        $this->assertSame($expectedParentId, $loaded->getParentId());
        $this->assertSame($expectedPublished, $loaded->getPublished());
        $this->assertSame($expectedModificationDate, $loaded->getModificationDate());
    }

    public function testRuntimeCacheReturnsSameInstance(): void
    {
        $object = TestHelper::createEmptyObject('getbyid-rt-');
        $id = $object->getId();

        RuntimeCache::clear();

        $first = DataObject::getById($id);
        $second = DataObject::getById($id);

        $this->assertNotNull($first);
        $this->assertSame($first, $second, 'RuntimeCache hit must return the same instance');
    }

    public function testForceTrueBypassesRuntimeCache(): void
    {
        $object = TestHelper::createEmptyObject('getbyid-force-');
        $id = $object->getId();

        RuntimeCache::clear();

        $first = DataObject::getById($id);
        $second = DataObject::getById($id, ['force' => true]);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame($first, $second, 'force=true must bypass RuntimeCache and return a fresh instance');
        $this->assertSame($id, $second->getId());
    }

    public function testPostLoadEventIsDispatched(): void
    {
        $object = TestHelper::createEmptyObject('getbyid-event-');
        $id = $object->getId();

        RuntimeCache::clear();

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = \OpenDxp::getEventDispatcher();
        $dispatchedEvents = [];
        $listener = function (DataObjectEvent $event) use (&$dispatchedEvents): void {
            $dispatchedEvents[] = $event->getObject()->getId();
        };
        $dispatcher->addListener(DataObjectEvents::POST_LOAD, $listener);

        try {
            DataObject::getById($id);
        } finally {
            $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $listener);
        }

        $this->assertContains($id, $dispatchedEvents, 'POST_LOAD event must fire for a freshly loaded DataObject');
    }
}
