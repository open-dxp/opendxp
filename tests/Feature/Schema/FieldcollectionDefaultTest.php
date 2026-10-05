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

namespace OpenDxp\Tests\Feature\Schema;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Data\Unittestfieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Tests\Factory\UnittestFactory;

function localizedDefaultBecomes(string $value): void
{
    $definition = Definition::getByKey('unittestfieldcollection');
    $fields = $definition->getFieldDefinitions();
    $localized = $fields['localizedfields'];

    foreach ($localized->getChildren() as $child) {
        if ($child->getName() === 'linput') {
            $child->setDefaultValue($value);
        }
    }

    $definition->setFieldDefinitions($fields);
    $definition->save();
}

function reloaded(DataObject\Concrete $object): DataObject\Concrete
{
    return DataObject::getById($object->getId(), ['force' => true]);
}

function itemsOf(DataObject\Concrete $object): Fieldcollection
{
    return reloaded($object)->getFieldcollection();
}

beforeEach(fn () => $this->written = []);

afterEach(function () {
    localizedDefaultBecomes('');

    foreach ($this->written as $object) {
        $object->delete();
    }
});

it('hands a default that was added later only to an item that is new', function () {

    $object = UnittestFactory::createOne();
    $this->written[] = $object;
    $items = new Fieldcollection();
    $items->add((new Unittestfieldcollection())->setLinput('', 'en'));
    $items->add(new Unittestfieldcollection());

    $object->setFieldcollection($items);
    $object->save();

    localizedDefaultBecomes('1234');

    $again = reloaded($object);
    $again->getFieldcollection()->add(new Unittestfieldcollection());
    $again->save();

    $after = itemsOf($object);

    expect($after->get(0)->getLinput('en'))
        ->toBeNull()
        ->and($after->get(1)->getLinput('en'))
        ->toBeNull()
        ->and($after->get(2)->getLinput('en'))
        ->toBe('1234');
});

it('hands a default to every item of an object written after it was added', function () {

    localizedDefaultBecomes('1234');

    $object = UnittestFactory::createOne();
    $this->written[] = $object;
    $items = new Fieldcollection();
    $items->add(new Unittestfieldcollection());

    $object->setFieldcollection($items);
    $object->save();

    expect(itemsOf($object)->get(0)->getLinput('en'))->toBe('1234');
});
