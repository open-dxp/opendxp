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

namespace OpenDxp\Tests\Model\Document;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Folder;
use OpenDxp\Model\Document\Page;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;

/**
 * Pins down {@see Document::getById()} class-resolution behavior. The
 * proposed reflection-cache optimization (caching isAbstract per class) must
 * not change which class is instantiated for which document type.
 *
 * @group model.document.getbyid
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
        $this->assertNull(Document::getById(0));
        $this->assertNull(Document::getById(-5));
        $this->assertNull(Document::getById(99999999));
    }

    public function testPageDocumentLoadsAsPage(): void
    {
        $page = TestHelper::createEmptyDocumentPage('getbyid-page-');
        $id = $page->getId();

        RuntimeCache::clear();

        $loaded = Document::getById($id);
        $this->assertInstanceOf(Page::class, $loaded);
        $this->assertSame($id, $loaded->getId());
    }

    public function testFolderDocumentLoadsAsFolder(): void
    {
        $folder = Document\Service::createFolderByPath('/getbyid-folder-' . uniqid());
        $id = $folder->getId();

        RuntimeCache::clear();

        $loaded = Document::getById($id);
        $this->assertInstanceOf(Folder::class, $loaded);
        $this->assertSame($id, $loaded->getId());
    }

    public function testCallingGetByIdOnConcreteSubclassReturnsThatSubclass(): void
    {
        $page = TestHelper::createEmptyDocumentPage('getbyid-concrete-');
        $id = $page->getId();

        RuntimeCache::clear();

        $loaded = Page::getById($id);
        $this->assertInstanceOf(Page::class, $loaded);
        $this->assertSame($id, $loaded->getId());
    }

    public function testCallingPageGetByIdOnNonPageReturnsNull(): void
    {
        $folder = Document\Service::createFolderByPath('/getbyid-page-mismatch-' . uniqid());
        $id = $folder->getId();

        RuntimeCache::clear();

        $loaded = Page::getById($id);
        $this->assertNull($loaded, 'Page::getById on a Folder document must return null');
    }

    public function testRepeatedLoadsResolveSameClass(): void
    {
        $page = TestHelper::createEmptyDocumentPage('getbyid-repeated-');
        $id = $page->getId();

        RuntimeCache::clear();

        // First load (cold) — class resolution runs
        $first = Document::getById($id);
        // Force reload — class resolution runs again
        $second = Document::getById($id, ['force' => true]);
        // Third load (warm) from RuntimeCache
        $third = Document::getById($id);

        $this->assertInstanceOf(Page::class, $first);
        $this->assertInstanceOf(Page::class, $second);
        $this->assertInstanceOf(Page::class, $third);
    }

    public function testPostLoadEventFiresForDocument(): void
    {
        $page = TestHelper::createEmptyDocumentPage('getbyid-event-');
        $id = $page->getId();

        RuntimeCache::clear();

        $dispatcher = \OpenDxp::getEventDispatcher();
        $captured = [];
        $listener = function (DocumentEvent $event) use (&$captured): void {
            $captured[] = $event->getDocument()->getId();
        };
        $dispatcher->addListener(DocumentEvents::POST_LOAD, $listener);

        try {
            Document::getById($id);
        } finally {
            $dispatcher->removeListener(DocumentEvents::POST_LOAD, $listener);
        }

        $this->assertContains($id, $captured, 'POST_LOAD must fire when loading a document');
    }

    public function testGetByIdSucceedsWithoutAnyPostLoadListener(): void
    {
        $page = TestHelper::createEmptyDocumentPage('getbyid-nolistener-');
        $id = $page->getId();

        RuntimeCache::clear();

        // Nothing fancy — just verify the load completes when no POST_LOAD
        // listener is registered. The proposed `hasListeners()` short-circuit
        // must not change this happy path.
        $loaded = Document::getById($id);
        $this->assertInstanceOf(Page::class, $loaded);
    }
}
