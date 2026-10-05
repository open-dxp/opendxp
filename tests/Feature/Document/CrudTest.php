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

use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Document\Service;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;

it('hands back the controller it was saved with', function () {

    $page = DocumentPageFactory::createOne();

    $page->setController('App\Controller\NewsController::listingAction');
    $page->save();

    expect(Page::getById($page->getId(), ['force' => true])->getController())
        ->toBe('App\Controller\NewsController::listingAction');
});

it('is found under its new path once it was moved and renamed', function () {

    $page = DocumentPageFactory::createOne();
    $folder = DocumentFolderFactory::createOne();

    $page->setParentId($folder->getId());
    $page->setKey($page->getKey() . '_new');
    $page->save();

    $moved = Page::getByPath(sprintf('%s/%s', $folder->getFullPath(), $page->getKey()));

    expect($moved)
        ->toBeInstanceOf(Page::class)
        ->and($moved->getId())
        ->toBe($page->getId())
        ->and($folder->hasChildren())
        ->toBeTrue();
});

it('is gone and leaves its folder childless once it was deleted', function () {

    $folder = DocumentFolderFactory::createOne();
    $page = DocumentPageFactory::createOne(['parentId' => $folder->getId()]);

    $page->delete();

    expect(Page::getById($page->getId(), ['force' => true]))
        ->toBeNull()
        ->and($folder->hasChildren())
        ->toBeFalse();
});

it('tells a path it holds a document under from one it does not', function () {

    $page = DocumentPageFactory::createOne();

    expect(Service::pathExists($page->getRealFullPath()))
        ->toBeTrue()
        ->and(Service::pathExists($page->getRealFullPath() . '-nope'))
        ->toBeFalse();
});
