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

use Closure;
use OpenDxp\HttpCache\HttpCacheScope;
use RuntimeException;

dataset('states before collecting', [
    'never enabled' => fn (HttpCacheScope $scope) => null,
    'enabled' => fn (HttpCacheScope $scope) => $scope->enable(),
    'disabled after it was enabled' => function (HttpCacheScope $scope) {
        $scope->enable();
        $scope->disable();
    },
]);

beforeEach(fn () => $this->scope = new HttpCacheScope());

it('is inactive until something enables it', function () {
    expect($this->scope->isActive())->toBeFalse();
});

it('is active once it is enabled', function () {
    $this->scope->enable();

    expect($this->scope->isActive())->toBeTrue();
});

it('is inactive once it is disabled', function () {
    $this->scope->enable();

    $this->scope->disable();

    expect($this->scope->isActive())->toBeFalse();
});

it('stays inactive when it is enabled after a disable', function () {
    $this->scope->disable();

    $this->scope->enable();

    expect($this->scope->isActive())->toBeFalse();
});

it('is inactive after a reset', function () {
    $this->scope->enable();

    $this->scope->reset();

    expect($this->scope->isActive())->toBeFalse();
});

it('can be enabled again after a reset', function () {
    $this->scope->enable();
    $this->scope->disable();
    $this->scope->reset();

    $this->scope->enable();

    expect($this->scope->isActive())->toBeTrue();
});

it('is inactive inside a suspended block', function () {
    $this->scope->enable();

    $activeInside = $this->scope->suspended(fn () => $this->scope->isActive());

    expect($activeInside)->toBeFalse();
});

it('is active again after a suspended block', function () {
    $this->scope->enable();

    $this->scope->suspended(fn () => null);

    expect($this->scope->isActive())->toBeTrue();
});

it('is active again after a suspended block throws', function () {
    $this->scope->enable();

    expect(fn () => $this->scope->suspended(fn () => throw new RuntimeException('The block failed.')))
        ->toThrow(RuntimeException::class, 'The block failed.')
        ->and($this->scope->isActive())
        ->toBeTrue();
});

it('returns what a suspended block returns', function () {
    $result = $this->scope->suspended(fn () => 42);

    expect($result)->toBe(42);
});

it('is active inside a collecting block', function (Closure $state) {
    $state($this->scope);

    $activeInside = $this->scope->collecting(fn () => $this->scope->isActive());

    expect($activeInside)->toBeTrue();
})->with('states before collecting');

it('restores its state after a collecting block', function (Closure $state) {
    $state($this->scope);
    $activeBefore = $this->scope->isActive();

    $this->scope->collecting(fn () => null);

    expect($this->scope->isActive())->toBe($activeBefore);
})->with('states before collecting');

it('restores its state after a collecting block throws', function () {
    $this->scope->enable();
    $this->scope->disable();

    expect(fn () => $this->scope->collecting(fn () => throw new RuntimeException('The block failed.')))
        ->toThrow(RuntimeException::class, 'The block failed.')
        ->and($this->scope->isActive())
        ->toBeFalse();
});

it('returns what a collecting block returns', function () {
    $result = $this->scope->collecting(fn () => 42);

    expect($result)->toBe(42);
});
