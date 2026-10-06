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

namespace OpenDxp\Tests\Feature\Tool;

use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tool\Frontend;

beforeEach(function () {
    $this->site = SiteFactory::createOne();
    $this->otherSite = SiteFactory::createOne();
    $this->page = DocumentPageFactory::new()
        ->withParent($this->site->getRootDocument())
        ->create();
});

it('counts a document below the root of a site as part of that site', function () {
    $inSite = Frontend::isDocumentInSite($this->site, $this->page);

    expect($inSite)->toBeTrue();
});

it('counts the root document itself as part of its site', function () {
    $root = $this->site->getRootDocument();

    $inSite = Frontend::isDocumentInSite($this->site, $root);

    expect($inSite)->toBeTrue();
});

it('counts a document of one site as no part of another', function () {
    $inSite = Frontend::isDocumentInSite($this->otherSite, $this->page);

    expect($inSite)->toBeFalse();
});
