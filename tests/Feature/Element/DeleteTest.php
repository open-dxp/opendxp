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

namespace OpenDxp\Tests\Feature\Element;

it('removes a deleted element from its folder', function (string $factory, string $folderFactory) {
    $folder = $folderFactory::createOne();
    $element = $factory::new()
        ->withParent($folder)
        ->create();

    $element->delete();

    expect($element::getById(
        $element->getId(),
        ['force' => true],
    ))
        ->toBeNull()
        ->and($folder->hasChildren())
        ->toBeFalse();
})->with('elements with folders');
