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

use OpenDxp\Test\Factory\DocumentLinkFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;

beforeEach(fn () => $this->target = DocumentPageFactory::createOne());

it('loads the document it points at', function () {
    $link = DocumentLinkFactory::new()
        ->withTarget($this->target)
        ->create();

    $element = reloaded($link)->getElement();

    expect($element->getId())->toBe($this->target->getId());
});

it('drops a target that is the link itself', function () {
    $link = DocumentLinkFactory::new()
        ->withTarget($this->target)
        ->create();

    $link->setInternal($link->getId());
    $link->save();

    expect(reloaded($link)->getInternal())->toBeNull();
});
