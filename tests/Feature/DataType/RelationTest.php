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
    $assigned = array_map(
        static function (Unittest $target): ObjectMetadata {
            $metadata = new ObjectMetadata('objectswithmetadata', ['meta1', 'meta2'], $target);
            $metadata->setMeta1('value11');
            $metadata->setMeta2('value21');

            return $metadata;
        },
        UnittestFactory::createMany(4),
    );

    $object = UnittestFactory::createOne(['objectswithmetadata' => $assigned]);

    expect(reloaded($object))->toCarryField('objectswithmetadata', $assigned);
});

it('lists the objects that point at it', function () {
    $target = UnittestFactory::createOne();

    $object = UnittestFactory::createOne(['objects' => [$target]]);
    $pointing = reloaded($target)->getNonowner();

    expect($pointing)
        ->toHaveCount(1)
        ->and($pointing[0])
        ->getId()
        ->toBe($object->getId());
});

it('lists the objects that point at it without a reload', function () {
    $target = UnittestFactory::createOne();

    $object = UnittestFactory::createOne(['objects' => [$target]]);
    $pointing = $target->getNonowner();

    expect($pointing)
        ->toHaveCount(1)
        ->and($pointing[0])
        ->getId()
        ->toBe($object->getId());
});
