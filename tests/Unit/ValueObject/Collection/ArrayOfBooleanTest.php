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

use OpenDxp\ValueObject\Collection\ArrayOfBoolean;
use ValueError;

it('refuses an array holding something that is not a boolean', function () {
    new ArrayOfBoolean([1]);
})->throws(ValueError::class, 'Provided array must contain only boolean values. (integer given)');

it('accepts an array of booleans', function () {
    $booleans = new ArrayOfBoolean([true]);

    expect($booleans)->getValue()->toBe([true]);
});

it('checks the booleans again when they are unserialized', function () {
    $serialized = serialize(new ArrayOfBoolean([true]));
    $tampered = str_replace('b:1', 's:4:"true"', $serialized);

    expect(fn () => unserialize($tampered))
        ->toThrow(ValueError::class, 'Provided array must contain only boolean values. (string given)');
});
