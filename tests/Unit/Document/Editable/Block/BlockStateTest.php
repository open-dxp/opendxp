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

describe('blocks', function () {

    it('holds none to begin with', function () {
        expect($this->state->hasBlocks())
            ->toBeFalse()
            ->and($this->state->getBlocks())
            ->toBeEmpty();
    });

    it('comes back in the order it was pushed', function () {

        $names = [new BlockName('A', 'realA'), new BlockName('B', 'realB')];

        $this->state->pushBlock($names[0]);
        $this->state->pushBlock($names[1]);

        expect($this->state->hasBlocks())
            ->toBeTrue()
            ->and($this->state->getBlocks())
            ->toEqual($names);
    });

    it('loses the one pushed last when it is popped', function () {

        $first = new BlockName('A', 'realA');
        $this->state->pushBlock($first);
        $this->state->pushBlock(new BlockName('B', 'realB'));

        $this->state->popBlock();

        expect($this->state->getBlocks())->toEqual([$first]);

        $this->state->popBlock();

        expect($this->state->hasBlocks())->toBeFalse();
    });

    it('refuses to pop when it holds none', function () {
        $this->state->popBlock();
    })->throws(UnderflowException::class);

    it('loses all of them at once when they are cleared', function () {

        $this->state->pushBlock(new BlockName('A', 'realA'));
        $this->state->pushBlock(new BlockName('B', 'realB'));

        $this->state->clearBlocks();

        expect($this->state->hasBlocks())
            ->toBeFalse()
            ->and($this->state->getBlocks())
            ->toBeEmpty();
    });
});

describe('indexes', function () {

    it('holds none to begin with', function () {
        expect($this->state->hasIndexes())
            ->toBeFalse()
            ->and($this->state->getIndexes())
            ->toBeEmpty();
    });

    it('comes back in the order it was pushed', function () {

        $this->state->pushIndex(1);
        $this->state->pushIndex(2);

        expect($this->state->hasIndexes())
            ->toBeTrue()
            ->and($this->state->getIndexes())
            ->toBe([1, 2]);
    });

    it('loses the one pushed last when it is popped', function () {

        $this->state->pushIndex(1);
        $this->state->pushIndex(2);

        $this->state->popIndex();

        expect($this->state->getIndexes())->toBe([1]);

        $this->state->popIndex();

        expect($this->state->hasIndexes())->toBeFalse();
    });

    it('refuses to pop when it holds none', function () {
        $this->state->popIndex();
    })->throws(UnderflowException::class);

    it('loses all of them at once when they are cleared', function () {

        $this->state->pushIndex(1);
        $this->state->pushIndex(2);

        $this->state->clearIndexes();

        expect($this->state->hasIndexes())
            ->toBeFalse()
            ->and($this->state->getIndexes())
            ->toBeEmpty();
    });
});
