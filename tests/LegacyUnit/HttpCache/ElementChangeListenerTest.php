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

namespace OpenDxp\Tests\Unit\HttpCache;

use OpenDxp\Bundle\CoreBundle\EventListener\HttpCache\ElementChangeListener;
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Event\Model\TranslationEvent;
use OpenDxp\HttpCache\HttpCache;
use OpenDxp\HttpCache\HttpCacheArguments;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Model\Translation;
use OpenDxp\Tests\Support\Test\TestCase;

class ElementChangeListenerTest extends TestCase
{
    private HttpCache $invalidator;

    private ElementChangeListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invalidator = $this->createMock(HttpCache::class);
        $this->listener = new ElementChangeListener($this->invalidator);
    }

    public function testDocumentChangeDelegatesToInvalidator(): void
    {
        $document = $this->createMock(Document::class);

        $event = $this->createMock(DocumentEvent::class);
        $event->method('getElement')->willReturn($document);
        $event->method('hasArgument')->willReturn(false);

        $this->invalidator->expects($this->once())->method('invalidate')->with($document);

        $this->listener->onDocumentChange($event);
    }

    public function testDocumentSaveVersionOnlySkipsInvalidation(): void
    {
        $this->invalidator->expects($this->never())->method('invalidate');

        $this->listener->onDocumentChange($this->makeDocumentEvent(['saveVersionOnly' => true]));
    }

    public function testDocumentAutoSaveSkipsInvalidation(): void
    {
        $this->invalidator->expects($this->never())->method('invalidate');

        $this->listener->onDocumentChange($this->makeDocumentEvent(['autoSave' => true]));
    }

    public function testDataObjectChangeDelegatesToInvalidator(): void
    {
        $object = $this->createMock(Concrete::class);

        $event = $this->createMock(DataObjectEvent::class);
        $event->method('getElement')->willReturn($object);
        $event->method('hasArgument')->willReturn(false);

        $this->invalidator->expects($this->once())->method('invalidate')->with($object);

        $this->listener->onDataObjectChange($event);
    }

    public function testDataObjectSaveVersionOnlySkipsInvalidation(): void
    {
        $this->invalidator->expects($this->never())->method('invalidate');

        $object = $this->createMock(Concrete::class);

        $event = $this->createMock(DataObjectEvent::class);
        $event->method('getElement')->willReturn($object);
        $event->method('hasArgument')->willReturnMap([['saveVersionOnly', true], ['autoSave', false]]);

        $this->listener->onDataObjectChange($event);
    }

    public function testAssetChangeDelegatesToInvalidator(): void
    {
        $asset = $this->createMock(Asset::class);

        $event = $this->createMock(AssetEvent::class);
        $event->method('getAsset')->willReturn($asset);
        $event->method('hasArgument')->willReturn(false);

        $this->invalidator->expects($this->once())->method('invalidate')->with($asset);

        $this->listener->onAssetChange($event);
    }

    public function testTranslationChangeDelegatesToInvalidator(): void
    {
        $event = $this->createMock(TranslationEvent::class);
        $event->method('getTranslation')->willReturn(new Translation());
        $event->method('hasArgument')->willReturn(false);

        $this->invalidator->expects($this->once())->method('invalidate');

        $this->listener->onTranslationChange($event);
    }

    public function testTranslationSkipInvalidationArgument(): void
    {
        $event = $this->createMock(TranslationEvent::class);
        $event->method('hasArgument')
            ->willReturnCallback(fn (string $key) => $key === HttpCacheArguments::SKIP_INVALIDATION);

        $this->invalidator->expects($this->never())->method('invalidate');

        $this->listener->onTranslationChange($event);
    }

    private function makeDocumentEvent(array $arguments = []): DocumentEvent
    {
        $event = $this->createMock(DocumentEvent::class);
        $event->method('getElement')->willReturn($this->createMock(Document::class));
        $event->method('hasArgument')->willReturnCallback(
            fn (string $key) => array_key_exists($key, $arguments)
        );

        return $event;
    }
}
