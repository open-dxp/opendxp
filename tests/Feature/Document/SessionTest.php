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

use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Model\Document\Service;
use OpenDxp\Model\Element\Service as ElementService;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;

it('keeps its editables through a trip into the session', function () {

    $editable = new Input();
    $editable->setName('testinput');
    $editable->setDataFromEditmode('foo');

    $page = DocumentPageFactory::new()->unpublished()->create();
    $page->setEditable($editable);

    $session = Container::requestStack()->getCurrentRequest()->getSession();
    ElementService::saveElementToSession($page, $session->getId());

    $restored = Service::getElementFromSession('document', $page->getId(), $session->getId());

    expect($restored->getEditables())->toHaveCount(count($page->getEditables()));
});
