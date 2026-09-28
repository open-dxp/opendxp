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

namespace OpenDxp\Tests\Model\Relations;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Model\DataObject\RelationTest;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;

/**
 * Class FieldcollectionTest
 *
 * @package OpenDxp\Tests\Model\Relations
 *
 * @group model.relations.fieldcollection
 */
class FieldcollectionTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupOpenDxpClass_RelationTest();
        $this->tester->setupFieldcollection_Unittestfieldcollection();
    }

    public function testRelationFieldInsideFieldCollection(): void
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey('mytarget1');
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey('mytarget2');
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey('mytarget3');
        $target3->setPublished(true);
        $target3->save();

        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setFieldRelation([$target1]);

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setFieldRelation([$target2]);

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setFieldRelation([$target3]);           // target3 instead of target2
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals($target1->getId(), $rel[0]->getId());

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals($target3->getId(), $rel[0]->getId());

        //Flush relations
        $loadedFieldcollectionItem->setFieldRelation(null);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals([], $rel);
    }

    public function testAdvancedRelationFieldInsideFieldCollection(): void
    {
        $object = TestHelper::createEmptyObject();
        $items = new Fieldcollection();

        $target1 = new Asset();
        $target1->setParent(Asset::getByPath('/'));
        $target1->setKey('mytarget1');
        $target1->save();

        $target2 = new Asset();
        $target2->setParent(Asset::getByPath('/'));
        $target2->setKey('mytarget2');
        $target2->save();

        $target3 = new Asset();
        $target3->setParent(Asset::getByPath('/'));
        $target3->setKey('mytarget3');
        $target3->save();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setAdvancedFieldRelation([new ElementMetadata('metadataUpper', [], $target1)]);

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setAdvancedFieldRelation([new ElementMetadata('metadataUpper', [], $target2)]);

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        // Test by deleting the target2 element
        $target2->delete();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        $object->save();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        //check if target1 is still there
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getAdvancedFieldRelation();
        $this->assertEquals($target1->getId(), isset($rel[0]) ? $rel[0]->getElementId() : false);

        //check if target2 is removed
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getAdvancedFieldRelation();
        $this->assertEquals(false, isset($rel[0]));
    }

    public function testLocalizedFieldInsideFieldCollection(): void
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey('mytarget1');
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey('mytarget2');
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey('mytarget3');
        $target3->setPublished(true);
        $target3->save();

        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setLinput('textEN', 'en');
        $item1->setLRelation($target1, 'en');

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setLinput('textEN', 'en');
        $item2->setLRelation($target2, 'en');

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setLRelation($target3, 'en');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals($target1->getId(), $rel->getId());

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals($target3->getId(), $rel->getId());

        //Flush relations
        $loadedFieldcollectionItem->setLRelation(null, 'en');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $lrel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals(null, $lrel);
    }

    public function testLocalizedFieldInsideFieldCollectionListing(): void
    {
        $object = TestHelper::createEmptyObject();

        $items = new Fieldcollection();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setFieldinput1('one');
        $item1->setLinput('hugo', 'en');

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setFieldinput1('two');
        $item2->setLinput('franz', 'en');

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        // item1's plain attribute together with item1's own localized attribute must match
        $list = new Unittest\Listing();
        $list->setLocale('en');
        $list->addFieldCollection('unittestfieldcollection', 'fieldcollection');
        $list->setCondition("`unittestfieldcollection~fieldcollection`.fieldinput1 = 'one' AND `unittestfieldcollection~fieldcollection_localized`.linput = 'hugo'");
        $list->load();
        $this->assertCount(1, $list->getObjects());
        $this->assertEquals($object->getId(), $list->getObjects()[0]->getId());

        // item1's plain attribute must never be paired with item2's localized attribute
        $list = new Unittest\Listing();
        $list->setLocale('en');
        $list->addFieldCollection('unittestfieldcollection', 'fieldcollection');
        $list->setCondition("`unittestfieldcollection~fieldcollection`.fieldinput1 = 'one' AND `unittestfieldcollection~fieldcollection_localized`.linput = 'franz'");
        $list->load();
        $this->assertCount(0, $list->getObjects());
    }

    public function testInputFieldWithNullAndThenDefaultValue(): void
    {
        //empty string
        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setLinput('', 'en');

        //null by default
        $item2 = new FieldCollection\Data\Unittestfieldcollection();

        //null set manually
        $item3 = new FieldCollection\Data\Unittestfieldcollection();
        $item3->setLinput(null, 'en');

        //save empty data for language "en"
        $items = new Fieldcollection();
        $items->add($item1);
        $items->add($item2);
        $items->add($item3);

        $object = TestHelper::createEmptyObject();
        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('', $linput);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(2);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        // Add default value afterwards
        $definition = Definition::getByKey('unittestfieldcollection');
        $fieldDefinitions = $definition->getFieldDefinitions();
        $children = $fieldDefinitions['localizedfields']->getChildren();
        foreach ($children as $index => $child) {
            if ($child->getName() === 'linput') {
                $children[$index]->setDefaultValue('1234');
            }
        }
        $fieldDefinitions['localizedfields']->setChildren($children);
        $definition->setFieldDefinitions($fieldDefinitions);
        $definition->save();

        // reload, resave and check
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $items = $object->getFieldcollection();
        // add a ex-novo item that should have default value
        $item4 = new FieldCollection\Data\Unittestfieldcollection();
        $items->add($item4);
        $object->setFieldcollection($items);
        $object->save();
        $object = DataObject::getById($object->getId(), ['force' => true]);

        //check all again
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('', $linput);

        //stays null, the default value is not applied retroactively
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        //stays null, the default value is not applied retroactively
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(2);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        //newly added with no values define taking default value
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(3);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('1234', $linput);

        //retest for a new object with the new definition with a default value
        $items = new Fieldcollection();
        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $items->add($item1);

        $object = TestHelper::createEmptyObject();
        $object->setFieldcollection($items);
        $object->save();

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('1234', $linput);
    }
}
