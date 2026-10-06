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

use OpenDxp\ValueObject\Collection\ArrayOfStrings;
use ValueError;

it('refuses an array holding something that is not a string', function () {
    new ArrayOfStrings([3]);
})->throws(ValueError::class, 'Provided array must contain only string values. (integer given)');

it('accepts an array of strings', function () {
    $strings = new ArrayOfStrings(['3']);

    expect($strings)->getValue()->toBe(['3']);
});

it('checks the strings again when they are unserialized', function () {
    $serialized = serialize(new ArrayOfStrings(['42']));
    $tampered = str_replace('s:2:"42"', 'i:42', $serialized);

    expect(fn () => unserialize($tampered))
        ->toThrow(ValueError::class, 'Provided array must contain only string values. (integer given)');
});
