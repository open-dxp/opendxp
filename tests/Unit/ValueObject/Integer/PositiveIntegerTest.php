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

it('answers with the integer it was given', function () {
    expect((new PositiveInteger(1))->getValue())->toBe(1);
});

it('equals another one holding the same integer', function () {

    $one = new PositiveInteger(1);

    expect($one->equals(new PositiveInteger(1)))
        ->toBeTrue()
        ->and($one->equals(new PositiveInteger(2)))
        ->toBeFalse();
});

it('checks the integer again when it comes back from a serialized form', function () {

    $serialized = str_replace('42', '-42', serialize(new PositiveInteger(42)));

    unserialize($serialized);
})->throws(ValueError::class, 'Provided integer must be positive. (-42 given)');
