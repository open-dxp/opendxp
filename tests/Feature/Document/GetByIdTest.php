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

namespace OpenDxp\Tests\Feature\Document;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Folder;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;

it('loads a folder as a folder', function () {

    $folder = DocumentFolderFactory::createOne();
    RuntimeCache::clear();

    expect(Document::getById($folder->getId()))->toBeInstanceOf(Folder::class);
});

it('loads a page through the subclass it was asked on', function () {

    $page = DocumentPageFactory::createOne();
    RuntimeCache::clear();

    expect(Page::getById($page->getId()))
        ->toBeInstanceOf(Page::class)
        ->and(Page::getById($page->getId())->getId())
        ->toBe($page->getId());
});

it('hands back nothing when the subclass asked for is not the one stored', function () {

    $folder = DocumentFolderFactory::createOne();
    RuntimeCache::clear();

    expect(Page::getById($folder->getId()))->toBeNull();
});

it('resolves the same class on every load, cold, forced and cached', function () {

    $page = DocumentPageFactory::createOne();
    RuntimeCache::clear();

    expect(Document::getById($page->getId()))
        ->toBeInstanceOf(Page::class)
        ->and(Document::getById($page->getId(), ['force' => true]))
        ->toBeInstanceOf(Page::class)
        ->and(Document::getById($page->getId()))
        ->toBeInstanceOf(Page::class);
});
