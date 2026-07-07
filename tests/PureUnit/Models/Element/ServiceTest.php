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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\PureUnit\Models\Element;

use Codeception\Test\Unit as TestCase;
use OpenDxp\Model\Element\Service;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * Pins down the contract of {@see Service::prepareGetByIdParams()} so that any
 * future fast-path optimization keeps the same input/output behavior.
 *
 * @group unit.element.service
 */
class ServiceTest extends TestCase
{
    public function testReturnsForceFalseByDefaultForEmptyParams(): void
    {
        $result = Service::prepareGetByIdParams([]);

        $this->assertSame(['force' => false], $result);
    }

    public function testPreservesForceFalseExplicitlyPassed(): void
    {
        $result = Service::prepareGetByIdParams(['force' => false]);

        $this->assertSame(['force' => false], $result);
    }

    public function testPreservesForceTrueExplicitlyPassed(): void
    {
        $result = Service::prepareGetByIdParams(['force' => true]);

        $this->assertSame(['force' => true], $result);
    }

    public function testRejectsNonBooleanForceValue(): void
    {
        $this->expectException(InvalidOptionsException::class);

        Service::prepareGetByIdParams(['force' => 'yes']);
    }

    public function testRejectsIntegerOneAsForceValue(): void
    {
        $this->expectException(InvalidOptionsException::class);

        Service::prepareGetByIdParams(['force' => 1]);
    }

    public function testRejectsNullAsForceValue(): void
    {
        $this->expectException(InvalidOptionsException::class);

        Service::prepareGetByIdParams(['force' => null]);
    }

    public function testRejectsUnknownOptionKey(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        Service::prepareGetByIdParams(['unknown' => true]);
    }

    public function testRejectsForceMixedWithUnknownKey(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        Service::prepareGetByIdParams(['force' => true, 'unknown' => 'x']);
    }

    public function testRepeatedCallsReturnIndependentArrays(): void
    {
        // Important once a static cached resolver is introduced: each call must
        // return a fresh array result and never leak state between calls.
        $a = Service::prepareGetByIdParams([]);
        $b = Service::prepareGetByIdParams(['force' => true]);
        $c = Service::prepareGetByIdParams([]);

        $this->assertSame(['force' => false], $a);
        $this->assertSame(['force' => true], $b);
        $this->assertSame(['force' => false], $c);
    }
}
