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
use RuntimeException;

beforeEach(fn () => $this->scope = new HttpCacheScope());

it('is inactive until something enables it', function () {
    expect($this->scope->isActive())->toBeFalse();
});

it('is active once it was enabled', function () {

    $this->scope->enable();

    expect($this->scope->isActive())->toBeTrue();
});

it('is inactive once it was disabled', function () {

    $this->scope->enable();
    $this->scope->disable();

    expect($this->scope->isActive())->toBeFalse();
});

it('stays inactive when it was disabled before anything enabled it', function () {

    $this->scope->disable();
    $this->scope->enable();

    expect($this->scope->isActive())->toBeFalse();
});

it('forgets a disable when it is reset', function () {

    $this->scope->enable();
    $this->scope->disable();
    $this->scope->reset();

    expect($this->scope->isActive())->toBeFalse();

    $this->scope->enable();

    expect($this->scope->isActive())->toBeTrue();
});

it('is inactive inside a suspended block and active again after it', function () {

    $this->scope->enable();
    $inside = null;
    $this->scope->suspended(function () use (&$inside) {
        $inside = $this->scope->isActive();
    });

    expect($inside)
        ->toBeFalse()
        ->and($this->scope->isActive())
        ->toBeTrue();
});

it('is active again when a suspended block throws', function () {

    $this->scope->enable();

    try {
        $this->scope->suspended(fn () => throw new RuntimeException('test'));
    } catch (RuntimeException) {
    }

    expect($this->scope->isActive())->toBeTrue();
});

it('hands back what a suspended block returns', function () {

    $this->scope->enable();

    expect($this->scope->suspended(fn () => 42))->toBe(42);
});

it('is inactive inside a suspended block while it is disabled', function () {

    $this->scope->enable();
    $this->scope->disable();
    $inside = true;
    $this->scope->suspended(function () use (&$inside) {
        $inside = $this->scope->isActive();
    });

    expect($inside)->toBeFalse();
});

it('is active inside a collecting block, whatever it was before', function (bool $disableFirst) {

    if ($disableFirst) {
        $this->scope->enable();
        $this->scope->disable();
    }

    $inside = false;
    $this->scope->collecting(function () use (&$inside) {
        $inside = $this->scope->isActive();
    });

    expect($inside)
        ->toBeTrue()
        ->and($this->scope->isActive())
        ->toBeFalse();
})->with([
    'untouched before' => [false],
    'disabled before' => [true],
]);

it('is disabled again when a collecting block throws', function () {

    $this->scope->enable();
    $this->scope->disable();

    try {
        $this->scope->collecting(fn () => throw new RuntimeException('test'));
    } catch (RuntimeException) {
    }

    expect($this->scope->isActive())->toBeFalse();
});

it('hands back what a collecting block returns', function () {
    expect($this->scope->collecting(fn () => 42))->toBe(42);
});
