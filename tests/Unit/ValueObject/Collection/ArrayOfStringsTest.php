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
    new ArrayOfStrings(['1', '2', 3]);
})->throws(ValueError::class, 'Provided array must contain only string values. (integer given)');

it('answers with the strings it was given', function () {
    expect((new ArrayOfStrings(['1', '2', '3']))->getValue())->toBe(['1', '2', '3']);
});

it('checks the strings again when they come back from a serialized form', function () {

    $serialized = str_replace('s:2:"42"', 'i:42', serialize(new ArrayOfStrings(['1', '2', '42'])));

    unserialize($serialized);
})->throws(ValueError::class, 'Provided array must contain only string values. (integer given)');
