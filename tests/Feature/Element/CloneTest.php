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


namespace OpenDxp\Tests\Feature\Element;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element\Service;
use OpenDxp\Tests\Factory\TestObjectFactory;

it('hands back a copy that belongs nowhere yet', function () {

    $copy = Service::cloneMe(TestObjectFactory::createOne());

    expect($copy->getId())
        ->toBeNull()
        ->and($copy->getParent())
        ->toBeNull()
        ->and($copy->getParentId())
        ->toBeNull();
});

it('carries the properties over and takes a free key', function () {

    $object = TestObjectFactory::createOne();
    $object->setProperty('propertyA', 'input', 'valueA');
    $object->save();

    $root = DataObject::getById(1);
    $copy = Service::cloneMe($object);
    $copy->setKey(Service::getSafeCopyName($copy->getKey(), $root));
    $copy->setParentId($root->getId());
    $copy->save();

    $saved = DataObject::getById($copy->getId(), ['force' => true]);

    expect($saved->getKey())
        ->toBe($object->getKey() . '_copy')
        ->and($saved->getProperty('propertyA'))
        ->toBe('valueA');
});
