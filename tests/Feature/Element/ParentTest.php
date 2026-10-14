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

it('refuses to be saved without a parent', function (string $factory, string $type, ?int $parentId) {
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setParentId($parentId);

    expect(fn () => $unsaved->save())->toThrow(
        Exception::class,
        sprintf(
            'New %s "%s" cannot be saved. It has no parent. Set the parent ID to 1 to place it in the root folder.',
            $type,
            $unsaved->getKey(),
        ),
    );
})
    ->with('element types')
    ->with([
        'a parent id of zero' => [0],
        'no parent id' => [null],
    ]);

it('refuses to be its own parent', function (string $factory, string $type) {
    $saved = $factory::createOne();
    $saved->setParentId($saved->getId());

    expect(fn () => $saved->save())->toThrow(
        Exception::class,
        sprintf(
            '%s %s (ID %d) cannot be saved. It cannot be its own parent.',
            ucfirst($type),
            $saved->getRealFullPath(),
            $saved->getId(),
        ),
    );
})->with('element types');

it('refuses a parent that does not exist', function (string $factory, string $type) {
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setParentId(999999);

    expect(fn () => $unsaved->save())->toThrow(
        Exception::class,
        sprintf(
            'New %s "%s" cannot be saved. Its parent 999999 does not exist.',
            $type,
            $unsaved->getKey(),
        ),
    );
})->with('element types');
