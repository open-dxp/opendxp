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

use OpenDxp\Bundle\CoreBundle\EventListener\HttpCache\HttpCachePostLoadListener;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Event\Model\ElementEventInterface;
use OpenDxp\HttpCache\HttpCache;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use Symfony\Component\EventDispatcher\EventDispatcher;

beforeEach(function () {
    $this->cache = $this->createMock(HttpCache::class);
    $this->dispatcher = new EventDispatcher();
    $this->dispatcher->addSubscriber(new HttpCachePostLoadListener($this->cache));
});

it('collects the tags of a loaded element', function (ElementEventInterface $event, string $eventName) {
    $this->cache
        ->expects($this->once())
        ->method('collectTagsFor')
        ->with($event->getElement());

    $this->dispatcher->dispatch($event, $eventName);
})->with([
    'a document' => [
        fn () => new DocumentEvent($this->createMock(Document::class)),
        DocumentEvents::POST_LOAD,
    ],
    'an object' => [
        fn () => new DataObjectEvent($this->createMock(DataObject::class)),
        DataObjectEvents::POST_LOAD,
    ],
    'an asset' => [
        fn () => new AssetEvent($this->createMock(Asset::class)),
        AssetEvents::POST_LOAD,
    ],
]);
