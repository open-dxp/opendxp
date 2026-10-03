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

use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\UnittestFactory;

function fieldinput1Invisible(bool $invisible): void
{
    $collection = Definition::getByKey('unittestfieldcollection');
    $fields = $collection->getFieldDefinitions();
    $fields['fieldinput1']->setInvisible($invisible);
    $collection->setFieldDefinitions($fields);
    $collection->save();
}

/**
 * The backend sends one item of the collection back without the fields the editor did not show.
 */
function editmodeItem(?string $value, bool $submitted): array
{
    $data = ['fieldinput2' => 'untouched'];

    if ($submitted) {
        $data['fieldinput1'] = $value;
    }

    return [[
        'data' => $data,
        'type' => 'unittestfieldcollection',
        'oIndex' => 0,
        'title' => 'unittestfieldcollection',
    ]];
}

function anObjectWithACollection(): Unittest
{
    $item = new Fieldcollection\Data\Unittestfieldcollection();
    $item->setFieldinput1('persisted value');

    return UnittestFactory::createOne(['fieldcollection' => new Fieldcollection([$item], 'fieldcollection')]);
}

beforeEach(fn () => fieldinput1Invisible(true));

afterEach(function () {
    fieldinput1Invisible(false);

    foreach ($this->written as $object) {
        $object->delete();
    }
});

it('takes the value of an invisible field that the backend did send', function () {

    $object = anObjectWithACollection();
    $this->written = [$object];

    $written = Unittest::getById($object->getId(), ['force' => true]);
    $definition = $written->getClass()->getFieldDefinition('fieldcollection');

    $read = $definition->getDataFromEditmode(editmodeItem('edited value', submitted: true), $written);

    expect($read->get(0)->getFieldinput1())->toBe('edited value');
});

it('keeps the stored value of an invisible field that the backend left out', function () {

    $object = anObjectWithACollection();
    $this->written = [$object];

    $written = Unittest::getById($object->getId(), ['force' => true]);
    $definition = $written->getClass()->getFieldDefinition('fieldcollection');

    $read = $definition->getDataFromEditmode(editmodeItem(null, submitted: false), $written);

    expect($read->get(0)->getFieldinput1())->toBe('persisted value');
});
