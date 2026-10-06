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
use OpenDxp\Document\Editable\Block\BlockState;
use OpenDxp\Document\Editable\Block\BlockStateStack;
use RuntimeException;

beforeEach(fn () => $this->stack = new BlockStateStack());

it('holds one state from the start', function () {
    expect($this->stack->count())->toBe(1);
});

it('returns to the previous state when the current one is popped', function () {
    $previous = new BlockState();
    $this->stack->push($previous);
    $this->stack->push();

    $this->stack->pop();

    expect($this->stack->getCurrentState())->toBe($previous);
});

it('refuses to pop the last state off the stack', function () {
    $this->stack->pop();
})->throws(LogicException::class, "Can't pop the last state off the stack");

it('refuses to return a current state while it holds none', function () {
    $this->stack->loadArray([]);

    expect(fn () => $this->stack->getCurrentState())
        ->toThrow(RuntimeException::class, 'State stack is empty');
});
