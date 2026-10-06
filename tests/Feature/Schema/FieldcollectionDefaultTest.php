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

function setLocalizedInputDefault(string $value): void
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

beforeEach(fn () => $this->written = []);

afterEach(function () {
    setLocalizedInputDefault('');

    foreach ($this->written as $object) {
        $object->delete();
    }
});

it('applies a later default only to new items', function () {
    $emptied = new Unittestfieldcollection();
    $emptied->setLinput('', 'en');
    $untouched = new Unittestfieldcollection();
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $emptied,
            $untouched,
        )
        ->create();
    $this->written[] = $object;
    setLocalizedInputDefault('1234');
    $loaded = reloaded($object);
    $loaded->getFieldcollection()->add(new Unittestfieldcollection());

    $loaded->save();

    $items = reloaded($object)->getFieldcollection();
    expect($items->get(0)->getLinput('en'))
        ->toBeNull()
        ->and($items->get(1)->getLinput('en'))
        ->toBeNull()
        ->and($items->get(2)->getLinput('en'))
        ->toBe('1234');
});

it('applies a default to every item of an object saved afterwards', function () {
    setLocalizedInputDefault('1234');
    $object = UnittestFactory::new()
        ->unsaved()
        ->withFieldcollection(
            'fieldcollection',
            new Unittestfieldcollection(),
        )
        ->create();
    $this->written[] = $object;

    $object->save();

    $items = reloaded($object)->getFieldcollection();
    expect($items->get(0)->getLinput('en'))->toBe('1234');
});
