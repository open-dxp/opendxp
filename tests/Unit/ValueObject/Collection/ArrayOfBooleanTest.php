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
    new ArrayOfBoolean([true, false, 1]);
})->throws(ValueError::class, 'Provided array must contain only boolean values. (integer given)');

it('answers with the booleans it was given', function () {
    expect((new ArrayOfBoolean([true, false, true]))->getValue())->toBe([true, false, true]);
});

it('checks the booleans again when they come back from a serialized form', function () {

    $serialized = str_replace('b:1', 's:4:"true"', serialize(new ArrayOfBoolean([true, false])));

    unserialize($serialized);
})->throws(ValueError::class, 'Provided array must contain only boolean values. (string given)');
