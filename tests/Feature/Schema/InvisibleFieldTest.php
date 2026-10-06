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

use OpenDxp\Model\DataObject\Fieldcollection\Data\Unittestfieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * The backend sends each item of the collection back with the fields the editor showed.
 *
 * @param array<string, string> $data
 *
 * @return list<array<string, mixed>>
 */
function editmodeItems(array $data): array
{
    return [
        [
            'data' => $data,
            'type' => 'unittestfieldcollection',
            'oIndex' => 0,
            'title' => 'unittestfieldcollection',
        ],
    ];
}

beforeEach(function () {
    $collection = Definition::getByKey('unittestfieldcollection');
    $collection->getFieldDefinition('fieldinput1')->setInvisible(true);
    $collection->save();

    $item = new Unittestfieldcollection();
    $item->setFieldinput1('persisted value');
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $item,
        )
        ->create();
    $this->object = reloaded($object);
    $this->field = $this->object->getClass()->getFieldDefinition('fieldcollection');
});

afterEach(function () {
    $collection = Definition::getByKey('unittestfieldcollection');
    $collection->getFieldDefinition('fieldinput1')->setInvisible(false);
    $collection->save();

    $this->object->delete();
});

it('takes the value of an invisible field that the backend sent', function () {
    $items = editmodeItems([
        'fieldinput1' => 'edited value',
        'fieldinput2' => 'untouched',
    ]);

    $read = $this->field->getDataFromEditmode($items, $this->object);

    expect($read->get(0)->getFieldinput1())->toBe('edited value');
});

it('keeps the stored value of an invisible field that the backend left out', function () {
    $items = editmodeItems(['fieldinput2' => 'untouched']);

    $read = $this->field->getDataFromEditmode($items, $this->object);

    expect($read->get(0)->getFieldinput1())->toBe('persisted value');
});
