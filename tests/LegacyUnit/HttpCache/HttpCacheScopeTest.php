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

namespace OpenDxp\Tests\Unit\HttpCache;

use OpenDxp\HttpCache\HttpCacheScope;
use OpenDxp\Tests\Support\Test\TestCase;
use RuntimeException;

class HttpCacheScopeTest extends TestCase
{
    private HttpCacheScope $scope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scope = new HttpCacheScope();
    }

    public function testInactiveByDefault(): void
    {
        $this->assertFalse($this->scope->isActive());
    }

    public function testEnableActivates(): void
    {
        $this->scope->enable();
        $this->assertTrue($this->scope->isActive());
    }

    public function testDisableDeactivates(): void
    {
        $this->scope->enable();
        $this->scope->disable();
        $this->assertFalse($this->scope->isActive());
    }

    public function testDisableBeforeEnableSurvivesEnable(): void
    {
        $this->scope->disable();
        $this->scope->enable(); // listener fires after dev disabled in kernel.request
        $this->assertFalse($this->scope->isActive());
    }

    public function testResetRestoresInitialState(): void
    {
        $this->scope->enable();
        $this->scope->disable();
        $this->scope->reset();
        $this->assertFalse($this->scope->isActive());

        $this->scope->enable();
        $this->assertTrue($this->scope->isActive());
    }

    public function testSuspendedSuppressesCollectionForDuration(): void
    {
        $this->scope->enable();

        $activeInsideSuspended = null;
        $this->scope->suspended(function () use (&$activeInsideSuspended) {
            $activeInsideSuspended = $this->scope->isActive();
        });

        $this->assertFalse($activeInsideSuspended);
        $this->assertTrue($this->scope->isActive());
    }

    public function testSuspendedRestoresOnException(): void
    {
        $this->scope->enable();

        try {
            $this->scope->suspended(function () {
                throw new RuntimeException('test');
            });
        } catch (RuntimeException) {
        }

        $this->assertTrue($this->scope->isActive());
    }

    public function testSuspendedReturnsCallableResult(): void
    {
        $this->scope->enable();
        $result = $this->scope->suspended(fn () => 42);
        $this->assertSame(42, $result);
    }

    public function testCollectingForceEnablesRegardlessOfState(): void
    {
        $activeInsideCollecting = false;
        $this->scope->collecting(function () use (&$activeInsideCollecting) {
            $activeInsideCollecting = $this->scope->isActive();
        });

        $this->assertTrue($activeInsideCollecting);
        $this->assertFalse($this->scope->isActive()); // restored
    }

    public function testCollectingOverridesDisable(): void
    {
        $this->scope->enable();
        $this->scope->disable();

        $activeInsideCollecting = false;
        $this->scope->collecting(function () use (&$activeInsideCollecting) {
            $activeInsideCollecting = $this->scope->isActive();
        });

        $this->assertTrue($activeInsideCollecting);
        $this->assertFalse($this->scope->isActive()); // disable restored
    }

    public function testCollectingRestoresStateOnException(): void
    {
        $this->scope->enable();
        $this->scope->disable();

        try {
            $this->scope->collecting(function () {
                throw new RuntimeException('test');
            });
        } catch (RuntimeException) {
        }

        $this->assertFalse($this->scope->isActive()); // disabled restored
    }

    public function testCollectingReturnsCallableResult(): void
    {
        $result = $this->scope->collecting(fn () => 42);
        $this->assertSame(42, $result);
    }

    public function testDisabledTakesPrecedenceOverSuspended(): void
    {
        $this->scope->enable();
        $this->scope->disable();

        $activeInsideSuspended = true;
        $this->scope->suspended(function () use (&$activeInsideSuspended) {
            $activeInsideSuspended = $this->scope->isActive();
        });

        $this->assertFalse($activeInsideSuspended);
    }
}
