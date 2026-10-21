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

use OpenDxp\Model\Element\DuplicateFullPathException;

it('refuses a path that another element holds', function (string $factory, string $type) {
    $holder = $factory::createOne();
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setKey($holder->getKey());

    expect(fn () => $unsaved->save())->toThrow(
        DuplicateFullPathException::class,
        sprintf(
            'New %s "%s" cannot be saved. The path %s is already taken by %s %d.',
            $type,
            $holder->getKey(),
            $holder->getRealFullPath(),
            $type,
            $holder->getId(),
        ),
    );
})->with('element types');
