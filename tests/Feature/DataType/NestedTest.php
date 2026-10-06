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

namespace OpenDxp\Tests\Feature\DataType;

use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('loads the relations of a brick on a cold load', function () {
    $object = UnittestFactory::new()
        ->withObjectbrick(
            'mybricks',
            UnittestBrick::class,
            [
                'brickInput' => 'brickinput1',
                'brickLazyRelation' => TestObjectFactory::createMany(15),
            ],
        )
        ->create();

    $brick = reloaded($object)->getMybricks()->getUnittestBrick();

    expect($brick)
        ->getBrickInput()
        ->toBe('brickinput1')
        ->getBrickLazyRelation()
        ->toHaveCount(15);
});

it('loads the relations of a field collection item on a cold load', function () {
    $item = new Fieldcollection\Data\Unittestfieldcollection();
    $item->setFieldinput1('field11');
    $item->setFieldinput2('field21');
    $item->setFieldRelation(TestObjectFactory::createMany(10));
    $item->setFieldLazyRelation(TestObjectFactory::createMany(15));
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'myfieldcollection',
            $item,
        )
        ->create();

    $items = reloaded($object)->getMyfieldcollection()->getItems();

    expect($items)
        ->toHaveCount(1)
        ->and($items[0])
        ->getFieldinput1()
        ->toBe('field11')
        ->getFieldinput2()
        ->toBe('field21')
        ->getFieldRelation()
        ->toHaveCount(10)
        ->getFieldLazyRelation()
        ->toHaveCount(15);
});
