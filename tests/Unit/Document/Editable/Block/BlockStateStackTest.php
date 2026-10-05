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

namespace OpenDxp\Tests\Unit\Document\Editable\Block;

use LogicException;
use OpenDxp\Document\Editable\Block\BlockStateStack;
use ReflectionClass;
use RuntimeException;

it('carries one state from the start', function () {
    expect((new BlockStateStack())->count())->toBe(1);
});

it('gives back the state below when the one above is popped', function () {

    $stack = new BlockStateStack();
    $stack->push();
    $second = $stack->getCurrentState();

    $stack->push();
    $stack->push();

    expect($stack->count())->toBe(4);

    $stack->pop();
    $stack->pop();

    expect($stack->count())
        ->toBe(2)
        ->and($stack->getCurrentState())
        ->toEqual($second);
});

it('refuses to pop the last state off the stack', function () {

    $stack = new BlockStateStack();
    $stack->push();
    $stack->pop();

    $stack->pop();
})->throws(LogicException::class, "Can't pop the last state off the stack");

it('refuses to name a current state while it carries none', function () {

    $stack = (new ReflectionClass(BlockStateStack::class))->newInstanceWithoutConstructor();

    expect($stack->count())->toBe(0);

    $stack->getCurrentState();
})->throws(RuntimeException::class, 'State stack is empty');
