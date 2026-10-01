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
use OpenDxp\ValueObject\Collection\ArrayOfStrings;
use ValueError;

/**
 * @internal
 */
final class ArrayOfStringsTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedArrayContainsNonStringValues(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only string values. (integer given)');

        new ArrayOfStrings(['1', '2', 3]);
    }

    public function testItShouldReturnValues(): void
    {
        $values = ['1', '2', '3'];
        $stringArray = new ArrayOfStrings($values);

        $this->assertSame($values, $stringArray->getValue());
    }

    public function testItShouldBeValidatedAfterUnSerialization(): void
    {
        $stringArray = new ArrayOfStrings(['1', '2', '42']);
        $serialized = serialize($stringArray);

        $serialized =  str_replace('s:2:"42"', 'i:42', $serialized);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only string values. (integer given)');
        unserialize($serialized);
    }
}
