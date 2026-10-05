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

use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\DocumentPageFactory;

// An installation starts with the document root, and a listing counts it like any other document.
const DOCUMENTS = 6;

beforeEach(fn () => DocumentPageFactory::createMany(DOCUMENTS - 1));

it('counts every document there is', function () {
    expect((new Document\Listing())->getTotalCount())->toBe(DOCUMENTS);
});

it('counts only as many as the limit allows', function () {

    $listing = new Document\Listing();
    $listing->setLimit(3);
    $listing->setOffset(1);

    expect($listing->getCount())->toBe(3);
});

it('counts what is left behind the offset', function () {

    $listing = new Document\Listing();
    $listing->setLimit(10);
    $listing->setOffset(1);

    expect($listing->getCount())->toBe(DOCUMENTS - 1);
});

it('counts the same once the listing was loaded', function () {

    $listing = new Document\Listing();
    $listing->setLimit(10);
    $listing->setOffset(1);
    $listing->load();

    expect($listing->getCount())
        ->toBe(DOCUMENTS - 1)
        ->and($listing->getTotalCount())
        ->toBe(DOCUMENTS);
});
