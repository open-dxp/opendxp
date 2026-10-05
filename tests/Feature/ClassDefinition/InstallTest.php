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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Feature\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\TestObject;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;

it('has installed the class the definition beside the tests describes', function () {
    expect(ClassDefinition::getByName('TestObject'))
        ->toBeInstanceOf(ClassDefinition::class)
        ->and(class_exists(TestObject::class))
        ->toBeTrue();
});

it('creates objects of the installed class', function () {

    $object = TestObjectFactory::createOne(['key' => 'first-object', 'title' => 'A title']);

    expect($object)
        ->toBeInstanceOf(TestObject::class)
        ->and($object->getId())
        ->toBeGreaterThan(0)
        ->and($object->getTitle())
        ->toBe('A title')
        ->and(TestObject::getById($object->getId())->getKey())
        ->toBe('first-object');
});

it('puts an object into a folder', function () {

    $folder = DataObjectFolderFactory::createOne(['key' => 'catalogue']);
    $object = TestObjectFactory::new()->withParent($folder)->create(['key' => 'second-object']);

    expect($object->getFullPath())->toBe('/catalogue/second-object');
});
