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

use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Application\Controller\TagCollectionController;

it('stores the controller a page is given later', function () {
    $controller = sprintf('%s::documentListingAction', TagCollectionController::class);
    $page = DocumentPageFactory::createOne();
    $page->setController($controller);

    $page->save();

    expect(reloaded($page)->getController())->toBe($controller);
});
