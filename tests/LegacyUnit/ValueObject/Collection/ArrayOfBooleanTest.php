<?php

declare(strict_types = 1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Unit\ValueObject\Collection;

use OpenDxp\Tests\Support\Test\TestCase;
use OpenDxp\ValueObject\Collection\ArrayOfBoolean;
use ValueError;

/**
 * @internal
 */
final class ArrayOfBooleanTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedArrayContainsNonBooleanValues(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only boolean values. (integer given)');

        new ArrayOfBoolean([true, false, 1]);
    }

    public function testItShouldReturnValues(): void
    {
        $values = [true, false, true];
        $booleanArray = new ArrayOfBoolean($values);

        $this->assertSame($values, $booleanArray->getValue());
    }

    public function testItShouldBeValidatedAfterUnSerialization(): void
    {
        $stringArray = new ArrayOfBoolean([true, false]);
        $serialized = serialize($stringArray);

        $serialized =  str_replace('i:42', 's:2:"42"', $serialized);
        $serialized = str_replace('b:1', 's:4:"true"', $serialized);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only boolean values. (string given)');
        unserialize($serialized);
    }
}
