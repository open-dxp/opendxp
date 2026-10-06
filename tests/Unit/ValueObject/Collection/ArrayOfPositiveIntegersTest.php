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

namespace OpenDxp\Tests\Unit\ValueObject\Collection;

use OpenDxp\ValueObject\Collection\ArrayOfPositiveIntegers;
use ValueError;

it('refuses an array holding an integer that is not positive', function () {
    new ArrayOfPositiveIntegers([-1]);
})->throws(ValueError::class, 'Provided integer must be positive. (-1 given)');

it('refuses an array holding something that is not an integer', function () {
    new ArrayOfPositiveIntegers(['3']);
})->throws(ValueError::class, 'Provided array must contain only integer values. (string given)');

it('accepts an array of positive integers', function () {
    $integers = new ArrayOfPositiveIntegers([3]);

    expect($integers)->getValue()->toBe([3]);
});

it('checks the integers again when they are unserialized', function () {
    $serialized = serialize(new ArrayOfPositiveIntegers([42]));
    $tampered = str_replace('42', '-42', $serialized);

    expect(fn () => unserialize($tampered))
        ->toThrow(ValueError::class, 'Provided integer must be positive. (-42 given)');
});
