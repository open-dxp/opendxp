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

use OpenDxp\Model\Document\Link;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentLinkFactory;

it('hands back the element it points at', function () {

    $target = AssetImageFactory::createOne();

    $link = DocumentLinkFactory::createOne([
        'internalType' => 'asset',
        'internal' => $target->getId(),
        'linktype' => 'internal',
    ]);

    expect(Link::getById($link->getId())->getElement()->getId())->toBe($target->getId());
});

it('points at nothing rather than at itself', function () {

    $link = DocumentLinkFactory::createOne([
        'internalType' => 'document',
        'internal' => 1,
        'linktype' => 'internal',
    ]);

    expect($link->getInternal())->toBe(1);

    $link->setInternal($link->getId());
    $link->save();

    expect(Link::getById($link->getId())->getInternal())->toBeNull();
});
