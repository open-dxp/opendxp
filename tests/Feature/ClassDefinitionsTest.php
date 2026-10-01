<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\TestObject;
use OpenDxp\Test\Factory\ObjectFolderFactory;
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

    $folder = ObjectFolderFactory::createOne(['key' => 'catalogue']);
    $object = TestObjectFactory::new()->withParent($folder)->create(['key' => 'second-object']);

    expect($object->getFullPath())->toBe('/catalogue/second-object');
});
