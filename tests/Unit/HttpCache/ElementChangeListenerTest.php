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
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Event\Model\ElementEventInterface;
use OpenDxp\Event\Model\TranslationEvent;
use OpenDxp\Event\TranslationEvents;
use OpenDxp\HttpCache\HttpCache;
use OpenDxp\HttpCache\HttpCacheArguments;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Model\Translation;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

beforeEach(function () {
    $this->invalidator = $this->createMock(HttpCache::class);
    $this->dispatcher = new EventDispatcher();
    $this->dispatcher->addSubscriber(new ElementChangeListener($this->invalidator));
});

it('invalidates a changed element', function (ElementEventInterface $event, string $eventName) {
    $this->invalidator
        ->expects($this->once())
        ->method('invalidate')
        ->with($event->getElement());

    $this->dispatcher->dispatch($event, $eventName);
})->with([
    'a document' => [
        fn () => new DocumentEvent($this->createMock(Document::class)),
        DocumentEvents::POST_UPDATE,
    ],
    'an object' => [
        fn () => new DataObjectEvent($this->createMock(Concrete::class)),
        DataObjectEvents::POST_UPDATE,
    ],
    'an asset' => [
        fn () => new AssetEvent($this->createMock(Asset::class)),
        AssetEvents::POST_UPDATE,
    ],
]);

it('invalidates a changed translation', function () {
    $translation = new Translation();
    $event = new TranslationEvent($translation);

    $this->invalidator
        ->expects($this->once())
        ->method('invalidate')
        ->with($translation);

    $this->dispatcher->dispatch($event, TranslationEvents::POST_SAVE);
});

it('does not invalidate a change it is told to skip', function (Event $event, string $eventName) {
    $this->invalidator
        ->expects($this->never())
        ->method('invalidate');

    $this->dispatcher->dispatch($event, $eventName);
})->with([
    'a document saved as a version only' => [
        fn () => new DocumentEvent(
            $this->createMock(Document::class),
            ['saveVersionOnly' => true],
        ),
        DocumentEvents::POST_UPDATE,
    ],
    'a document saved automatically' => [
        fn () => new DocumentEvent(
            $this->createMock(Document::class),
            ['autoSave' => true],
        ),
        DocumentEvents::POST_UPDATE,
    ],
    'an object saved as a version only' => [
        fn () => new DataObjectEvent(
            $this->createMock(Concrete::class),
            ['saveVersionOnly' => true],
        ),
        DataObjectEvents::POST_UPDATE,
    ],
    'a translation that skips invalidation' => [
        fn () => new TranslationEvent(
            new Translation(),
            [HttpCacheArguments::SKIP_INVALIDATION => true],
        ),
        TranslationEvents::POST_SAVE,
    ],
]);
