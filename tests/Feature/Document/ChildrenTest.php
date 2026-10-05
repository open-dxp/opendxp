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

use OpenDxp\Model\Document\Listing;
use OpenDxp\Test\Factory\DocumentPageFactory;

beforeEach(function () {
    $this->parent = DocumentPageFactory::createOne();
    $this->published = DocumentPageFactory::createOne(['parentId' => $this->parent->getId()]);
    $this->unpublished = DocumentPageFactory::new()->unpublished()->create(['parentId' => $this->parent->getId()]);
});

it('counts only the published children', function () {
    expect($this->parent->getChildren())->toHaveCount(1);
});

it('counts the unpublished children when it is asked to', function () {
    expect($this->parent->getChildren(true))->toHaveCount(2);
});

it('reports that it has children', function () {
    expect($this->parent->hasChildren())->toBeTrue();
});

it('counts only the published siblings', function () {
    expect($this->published->getSiblings())->toHaveCount(0);
});

it('counts the unpublished siblings when it is asked to', function () {
    expect($this->published->getSiblings(true))->toHaveCount(1);
});

it('hands back the children it was handed', function () {

    $listing = new Listing();
    $listing->setData([$this->published]);

    $this->parent->setChildren($listing);

    expect($this->parent->getChildren()->getDocuments()[0])->toBe($this->published);
});
