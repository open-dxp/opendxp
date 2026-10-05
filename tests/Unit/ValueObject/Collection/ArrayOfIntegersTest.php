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

use OpenDxp\ValueObject\Collection\ArrayOfIntegers;
use ValueError;

it('refuses an array holding something that is not an integer', function () {
    new ArrayOfIntegers([1, 2, '3']);
})->throws(ValueError::class, 'Provided array must contain only integer values. (string given)');

it('answers with the integers it was given', function () {
    expect((new ArrayOfIntegers([1, 2, 3]))->getValue())->toBe([1, 2, 3]);
});

it('checks the integers again when they come back from a serialized form', function () {

    $serialized = str_replace('i:42', 's:2:"42"', serialize(new ArrayOfIntegers([1, 2, 42])));

    unserialize($serialized);
})->throws(ValueError::class, 'Provided array must contain only integer values. (string given)');
