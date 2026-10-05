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

use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\UnittestFactory;

it('keeps the metadata a relation carries', function () {

    $targets = UnittestFactory::createMany(4);

    $assigned = array_map(static function (Unittest $target): ObjectMetadata {
        $metadata = new ObjectMetadata('objectswithmetadata', ['meta1', 'meta2'], $target);
        $metadata->setMeta1('value11');
        $metadata->setMeta2('value21');

        return $metadata;
    }, $targets);

    $object = UnittestFactory::createOne(['objectswithmetadata' => $assigned]);

    expect(Unittest::getById($object->getId(), ['force' => true]))
        ->toCarryField('objectswithmetadata', $assigned);
});

it('names the object that points at it as a relation of its own', function () {

    $target = UnittestFactory::createOne();
    $object = UnittestFactory::createOne(['objects' => [$target]]);

    expect($target->getNonowner())
        ->toHaveCount(1)
        ->and($target->getNonowner()[0]->getId())
        ->toBe($object->getId());

    $reloaded = Unittest::getById($target->getId(), ['force' => true]);

    expect($reloaded->getNonowner())
        ->toHaveCount(1)
        ->and($reloaded->getNonowner()[0]->getId())
        ->toBe($object->getId());
});
