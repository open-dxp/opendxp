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
use OpenDxp\Model\DataObject\Objectbrick\Data\TestFieldTypesBrick;
use OpenDxp\Test\Elements;
use OpenDxp\Tests\Factory\TestPlainObjectFactory;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

it('loads the relations of a brick on a cold load', function () {
    $object = TestFieldTypesFactory::new()
        ->withObjectbrick(
            'mybricks',
            TestFieldTypesBrick::class,
            [
                'brickInput' => 'brickinput1',
                'brickLazyRelation' => TestPlainObjectFactory::createMany(15),
            ],
        )
        ->create();

    $brick = Elements::refresh($object)->getMybricks()->getTestFieldTypesBrick();

    expect($brick)
        ->getBrickInput()
        ->toBe('brickinput1')
        ->getBrickLazyRelation()
        ->toHaveCount(15);
});

it('loads the relations of a field collection item on a cold load', function () {
    $item = new Fieldcollection\Data\TestFeature();
    $item->setName('field11');
    $item->setContent('field21');
    $item->setDocuments(TestPlainObjectFactory::createMany(10));
    $item->setDownloads(TestPlainObjectFactory::createMany(15));
    $object = TestFieldTypesFactory::new()
        ->withFieldcollection(
            'myfieldcollection',
            $item,
        )
        ->create();

    $items = Elements::refresh($object)->getMyfieldcollection()->getItems();

    expect($items)
        ->toHaveCount(1)
        ->and($items[0])
        ->getName()
        ->toBe('field11')
        ->getContent()
        ->toBe('field21')
        ->getDocuments()
        ->toHaveCount(10)
        ->getDownloads()
        ->toHaveCount(15);
});
