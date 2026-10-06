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

namespace OpenDxp\Tests\Feature\Relation;

use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Tests\Factory\RelationTestFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(function () {
    $this->targets = RelationTestFactory::createMany(2);
    $this->object = UnittestFactory::new()
        ->withObjectbrick(
            'mybricks',
            UnittestBrick::class,
            ['brickLazyRelation' => $this->targets],
        )
        ->create();
});

it('keeps the relations of a brick', function () {
    $relations = reloaded($this->object)->getMybricks()->getUnittestBrick()->getBrickLazyRelation();

    expect($relations)
        ->toHaveCount(2)
        ->and($relations[0]->getId())
        ->toBe($this->targets[0]->getId())
        ->and($relations[1]->getId())
        ->toBe($this->targets[1]->getId());
});

it('keeps the remaining relation of a brick', function () {
    $this->object->getMybricks()->getUnittestBrick()->setBrickLazyRelation([$this->targets[1]]);
    $this->object->save();

    $relations = reloaded($this->object)->getMybricks()->getUnittestBrick()->getBrickLazyRelation();
    expect($relations)
        ->toHaveCount(1)
        ->and($relations[0]->getId())
        ->toBe($this->targets[1]->getId());
});

it('keeps no relation in a brick that was emptied', function () {
    $this->object->getMybricks()->getUnittestBrick()->setBrickLazyRelation([]);
    $this->object->save();

    $brick = reloaded($this->object)->getMybricks()->getUnittestBrick();
    expect($brick)->getBrickLazyRelation()->toBe([]);
});
