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

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function coldLoad(int $id): Unittest
{
    RuntimeCache::clear();

    return Unittest::getById($id, ['force' => true]);
}

it('loads the relations of a brick on a cold load as well', function () {

    $object = UnittestFactory::createOne();

    $brick = new UnittestBrick($object);
    $brick->setBrickInput('brickinput1');
    $brick->setBrickLazyRelation(TestObjectFactory::createMany(15));
    $object->getMybricks()->setUnittestBrick($brick);
    $object->save();

    $written = coldLoad($object->getId())->getMybricks()->getUnittestBrick();

    expect($written->getBrickinput())
        ->toBe('brickinput1')
        ->and($written->getBrickLazyRelation())
        ->toHaveCount(15);

    $again = coldLoad($object->getId())->getMybricks()->getItems()[0];

    expect($again->getBrickLazyRelation())->toHaveCount(15);
});

it('loads the relations of a field collection item on a cold load as well', function () {

    $item = new Fieldcollection\Data\Unittestfieldcollection();
    $item->setFieldinput1('field11');
    $item->setFieldinput2('field21');
    $item->setFieldRelation(TestObjectFactory::createMany(10));
    $item->setFieldLazyRelation(TestObjectFactory::createMany(15));

    $object = UnittestFactory::createOne([
        'myfieldcollection' => new Fieldcollection([$item], 'myfieldcollection'),
    ]);

    $written = coldLoad($object->getId())->getMyfieldcollection();

    expect($written->getCount())->toBe(1);

    $first = $written->getItems()[0];

    expect($first->getFieldinput1())
        ->toBe('field11')
        ->and($first->getFieldinput2())
        ->toBe('field21')
        ->and($first->getFieldRelation())
        ->toHaveCount(10)
        ->and($first->getFieldLazyRelation())
        ->toHaveCount(15);

    $again = coldLoad($object->getId())->getMyfieldcollection()->getItems()[0];

    expect($again->getFieldRelation())
        ->toHaveCount(10)
        ->and($again->getFieldLazyRelation())
        ->toHaveCount(15);
});
