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

use OpenDxp\Model\DataObject\Objectbrick\Data\TestDimensions;
use OpenDxp\Model\DataObject\TestProduct;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\TestProductFactory;

beforeEach(function () {
    $this->parent = TestProductFactory::new()
        ->withObjectbrick(
            'attributes',
            TestDimensions::class,
            ['width' => 'text of the parent'],
        )
        ->create();
    $this->child = TestProductFactory::new()
        ->withParent($this->parent)
        ->withObjectbrick(
            'attributes',
            TestDimensions::class,
            ['height' => 'text of the child'],
        )
        ->create();
    $this->grandchild = TestProductFactory::new()
        ->withParent($this->child)
        ->create();
});

dataset('descendants', [
    'the child' => fn () => $this->child,
    'the grandchild' => fn () => $this->grandchild,
]);

it('gives a brick value of the parent to every descendant', function (TestProduct $descendant) {
    $brick = Elements::refresh($descendant)->getAttributes()->getTestDimensions();

    expect($brick)->getWidth()->toBe('text of the parent');
})->with('descendants');

it('keeps a brick value of the child next to an inherited one', function () {
    $brick = Elements::refresh($this->child)->getAttributes()->getTestDimensions();

    expect($brick)->getHeight()->toBe('text of the child');
});

it('gives every descendant the new brick value once the parent changed it', function (TestProduct $descendant) {
    $this->parent->getAttributes()->getTestDimensions()->setWidth('another text');
    $this->parent->save();

    $brick = Elements::refresh($descendant)->getAttributes()->getTestDimensions();

    expect($brick)->getWidth()->toBe('another text');
})->with('descendants');
