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
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\HttpCache\HttpCache;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;

beforeEach(function () {
    $this->invalidator = $this->createMock(HttpCache::class);
    $this->listener = new HttpCachePostLoadListener($this->invalidator);
});

it('hands an element that was loaded to the invalidator', function (string $event, string $element, string $listens) {

    $loaded = $this->createMock($element);
    $fired = $this->createMock($event);
    $fired->method('getElement')->willReturn($loaded);

    $this->invalidator->expects($this->once())->method('collectTagsFor')->with($loaded);

    $this->listener->{$listens}($fired);
})->with([
    'a document' => [DocumentEvent::class, Document::class, 'onDocumentPostLoad'],
    'an object' => [DataObjectEvent::class, DataObject::class, 'onDataObjectPostLoad'],
    'an asset' => [AssetEvent::class, Asset::class, 'onAssetPostLoad'],
]);
