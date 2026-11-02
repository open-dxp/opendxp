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

use OpenDxp\Model\DataObject\Fieldcollection\Data\TestFeature;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Test\Elements;
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
            'type' => 'TestFeature',
            'oIndex' => 0,
            'title' => 'TestFeature',
        ],
    ];
}

beforeEach(function () {
    $collection = Definition::getByKey('TestFeature');
    $collection->getFieldDefinition('name')->setInvisible(true);
    $collection->save();

    $item = new TestFeature();
    $item->setName('persisted value');
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $item,
        )
        ->create();
    $this->object = Elements::refresh($object);
    $this->field = $this->object->getClass()->getFieldDefinition('fieldcollection');
});

afterEach(function () {
    $collection = Definition::getByKey('TestFeature');
    $collection->getFieldDefinition('name')->setInvisible(false);
    $collection->save();

    $this->object->delete();
});

it('takes the value of an invisible field that the backend sent', function () {
    $items = editmodeItems([
        'name' => 'edited value',
        'content' => 'untouched',
    ]);

    $read = $this->field->getDataFromEditmode($items, $this->object);

    expect($read->get(0)->getName())->toBe('edited value');
});

it('keeps the stored value of an invisible field that the backend left out', function () {
    $items = editmodeItems(['content' => 'untouched']);

    $read = $this->field->getDataFromEditmode($items, $this->object);

    expect($read->get(0)->getName())->toBe('persisted value');
});
