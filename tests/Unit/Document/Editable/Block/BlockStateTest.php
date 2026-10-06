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

use OpenDxp\Document\Editable\Block\BlockName;
use OpenDxp\Document\Editable\Block\BlockState;
use UnderflowException;

beforeEach(fn () => $this->state = new BlockState());

it('holds no blocks at first', function () {
    expect($this->state->hasBlocks())
        ->toBeFalse()
        ->and($this->state->getBlocks())
        ->toBeEmpty();
});

it('returns its blocks in the order they were pushed', function () {
    $first = new BlockName('A', 'realA');
    $second = new BlockName('B', 'realB');

    $this->state->pushBlock($first);
    $this->state->pushBlock($second);

    expect($this->state->hasBlocks())
        ->toBeTrue()
        ->and($this->state->getBlocks())
        ->toBe([
            $first,
            $second,
        ]);
});

it('removes the block pushed last when it pops a block', function () {
    $first = new BlockName('A', 'realA');
    $this->state->pushBlock($first);
    $this->state->pushBlock(new BlockName('B', 'realB'));

    $this->state->popBlock();

    expect($this->state->getBlocks())->toBe([$first]);
});

it('refuses to pop a block while it holds none', function () {
    $this->state->popBlock();
})->throws(UnderflowException::class, 'There are no blocks to pop from as blocks list is empty');

it('removes every block when its blocks are cleared', function () {
    $this->state->pushBlock(new BlockName('A', 'realA'));
    $this->state->pushBlock(new BlockName('B', 'realB'));

    $this->state->clearBlocks();

    expect($this->state->hasBlocks())
        ->toBeFalse()
        ->and($this->state->getBlocks())
        ->toBeEmpty();
});

it('holds no indexes at first', function () {
    expect($this->state->hasIndexes())
        ->toBeFalse()
        ->and($this->state->getIndexes())
        ->toBeEmpty();
});

it('returns its indexes in the order they were pushed', function () {
    $this->state->pushIndex(1);
    $this->state->pushIndex(2);

    expect($this->state->hasIndexes())
        ->toBeTrue()
        ->and($this->state->getIndexes())
        ->toBe([
            1,
            2,
        ]);
});

it('removes the index pushed last when it pops an index', function () {
    $this->state->pushIndex(1);
    $this->state->pushIndex(2);

    $this->state->popIndex();

    expect($this->state->getIndexes())->toBe([1]);
});

it('refuses to pop an index while it holds none', function () {
    $this->state->popIndex();
})->throws(UnderflowException::class, 'There are no indexes to pop from as index list is empty');

it('removes every index when its indexes are cleared', function () {
    $this->state->pushIndex(1);
    $this->state->pushIndex(2);

    $this->state->clearIndexes();

    expect($this->state->hasIndexes())
        ->toBeFalse()
        ->and($this->state->getIndexes())
        ->toBeEmpty();
});
