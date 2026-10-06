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

namespace OpenDxp\Tests\Unit\ValueObject\Integer;

use OpenDxp\ValueObject\Integer\PositiveInteger;
use ValueError;

it('refuses an integer that is not positive', function () {
    new PositiveInteger(-1);
})->throws(ValueError::class, 'Provided integer must be positive. (-1 given)');

it('accepts a positive integer', function () {
    $integer = new PositiveInteger(1);

    expect($integer)->getValue()->toBe(1);
});

it('equals another one only when it holds the same integer', function (int $other, bool $equal) {
    $integer = new PositiveInteger(1);

    $result = $integer->equals(new PositiveInteger($other));

    expect($result)->toBe($equal);
})->with([
    'the same integer' => [1, true],
    'another integer' => [2, false],
]);

it('checks the integer again when it is unserialized', function () {
    $serialized = serialize(new PositiveInteger(42));
    $tampered = str_replace('42', '-42', $serialized);

    expect(fn () => unserialize($tampered))
        ->toThrow(ValueError::class, 'Provided integer must be positive. (-42 given)');
});
