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

use Exception;

it('refuses to be saved without a parent', function (string $element, string $factory, ?int $parentId) {
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setParentId($parentId);

    $unsaved->save();
})
    ->with('elements')
    ->with([
        'a parent id of zero' => [0],
        'no parent id' => [null],
    ])
    ->throws(
        Exception::class,
        'ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, '
        . 'consider setting ParentID to 1.',
    );

it('refuses to be its own parent', function (string $element, string $factory) {
    $saved = $factory::createOne();
    $saved->setParentId($saved->getId());

    $saved->save();
})
    ->with('elements')
    ->throws(
        Exception::class,
        "ParentID and ID are identical, an element can't be the parent of itself in the tree.",
    );

it('refuses a parent that does not exist', function (string $element, string $factory) {
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setParentId(999999);

    $unsaved->save();
})
    ->with('elements')
    ->throws(Exception::class, 'ParentID not found.');
