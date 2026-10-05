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

beforeEach(function () {
    $this->invalidator = $this->createMock(HttpCache::class);
    $this->listener = new ElementChangeListener($this->invalidator);
});

it('hands a changed element to the invalidator', function (string $event, string $element, string $getter, string $listens) {

    $changed = $this->createMock($element);
    $fired = $this->createMock($event);
    $fired->method($getter)->willReturn($changed);
    $fired->method('hasArgument')->willReturn(false);

    $this->invalidator->expects($this->once())->method('invalidate')->with($changed);

    $this->listener->{$listens}($fired);
})->with([
    'a document' => [DocumentEvent::class, Document::class, 'getElement', 'onDocumentChange'],
    'an object' => [DataObjectEvent::class, Concrete::class, 'getElement', 'onDataObjectChange'],
    'an asset' => [AssetEvent::class, Asset::class, 'getAsset', 'onAssetChange'],
]);

it('leaves the cache alone for a save that only writes a version', function (string $event, string $element, string $listens, string $argument) {

    $fired = $this->createMock($event);
    $fired->method('getElement')->willReturn($this->createMock($element));
    $fired->method('hasArgument')->willReturnCallback(fn (string $key) => $key === $argument);

    $this->invalidator->expects($this->never())->method('invalidate');

    $this->listener->{$listens}($fired);
})->with([
    'a document saved as a version' => [DocumentEvent::class, Document::class, 'onDocumentChange', 'saveVersionOnly'],
    'a document saved by itself' => [DocumentEvent::class, Document::class, 'onDocumentChange', 'autoSave'],
    'an object saved as a version' => [DataObjectEvent::class, Concrete::class, 'onDataObjectChange', 'saveVersionOnly'],
]);

it('hands a changed translation to the invalidator', function () {

    $fired = $this->createMock(TranslationEvent::class);
    $fired->method('getTranslation')->willReturn(new Translation());
    $fired->method('hasArgument')->willReturn(false);

    $this->invalidator->expects($this->once())->method('invalidate');

    $this->listener->onTranslationChange($fired);
});

it('leaves the cache alone for a translation that asks to be skipped', function () {

    $fired = $this->createMock(TranslationEvent::class);
    $fired->method('hasArgument')
        ->willReturnCallback(fn (string $key) => $key === HttpCacheArguments::SKIP_INVALIDATION);

    $this->invalidator->expects($this->never())->method('invalidate');

    $this->listener->onTranslationChange($fired);
});
