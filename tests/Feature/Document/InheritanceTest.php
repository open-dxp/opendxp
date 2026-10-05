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

use Exception;
use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentPageFactory;

function pageWithHeadline(string $headline): Page
{
    $editable = new Input();
    $editable->setName('headline');
    $editable->setDataFromResource($headline);

    $page = DocumentPageFactory::createOne();
    $page->setEditable($editable);
    $page->save();

    return $page;
}

it('hands back the editable it was saved with', function () {

    $page = pageWithHeadline('test');

    expect(Page::getById($page->getId(), ['force' => true])->getEditable('headline')->getValue())->toBe('test');
});

it('carries no editable of a page it is not bound to', function () {

    pageWithHeadline('test');

    expect(DocumentPageFactory::createOne()->getEditable('headline'))->toBeNull();
});

it('still carries no editable once it only became a child', function () {

    $main = pageWithHeadline('test');
    $child = DocumentPageFactory::createOne(['parentId' => $main->getId()]);

    expect(Page::getById($child->getId(), ['force' => true])->getEditable('headline'))->toBeNull();
});

it('takes the editable over once it names a main document', function () {

    $main = pageWithHeadline('test');
    $child = DocumentPageFactory::createOne(['parentId' => $main->getId()]);

    $child->setContentMainDocumentId($main->getId(), true);
    $child->save();

    expect(Page::getById($child->getId(), ['force' => true])->getEditable('headline')->getValue())->toBe('test');
});

it('refuses a main document that already points back at it', function () {

    $first = DocumentPageFactory::createOne();
    $second = DocumentPageFactory::createOne();

    $first->setContentMainDocumentId($second->getId(), true);
    $first->save();

    $second->setContentMainDocumentId($first->getId(), true);
})->throws(Exception::class, 'This document is already part of the main document chain, please choose a different one.');
