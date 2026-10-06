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

namespace OpenDxp\Tests\Feature\Inheritance;

use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Tests\Factory\InheritanceFactory;

beforeEach(function () {
    $this->parent = InheritanceFactory::new()
        ->withObjectbrick(
            'mybricks',
            UnittestBrick::class,
            ['brickinput' => 'text of the parent'],
        )
        ->create();
    $this->child = InheritanceFactory::new()
        ->withParent($this->parent)
        ->withObjectbrick(
            'mybricks',
            UnittestBrick::class,
            ['brickinput2' => 'text of the child'],
        )
        ->create();
    $this->grandchild = InheritanceFactory::new()
        ->withParent($this->child)
        ->create();
});

dataset('descendants', [
    'the child' => fn () => $this->child,
    'the grandchild' => fn () => $this->grandchild,
]);

it('gives a brick value of the parent to every descendant', function (Inheritance $descendant) {
    $brick = reloaded($descendant)->getMybricks()->getUnittestBrick();

    expect($brick)->getBrickinput()->toBe('text of the parent');
})->with('descendants');

it('keeps a brick value of the child next to an inherited one', function () {
    $brick = reloaded($this->child)->getMybricks()->getUnittestBrick();

    expect($brick)->getBrickinput2()->toBe('text of the child');
});

it('gives every descendant the new brick value once the parent changed it', function (Inheritance $descendant) {
    $this->parent->getMybricks()->getUnittestBrick()->setBrickinput('another text');
    $this->parent->save();

    $brick = reloaded($descendant)->getMybricks()->getUnittestBrick();

    expect($brick)->getBrickinput()->toBe('another text');
})->with('descendants');
