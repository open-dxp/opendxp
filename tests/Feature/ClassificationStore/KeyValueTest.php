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


namespace OpenDxp\Tests\Feature\ClassificationStore;

use Closure;
use OpenDxp\Model\DataObject\Classificationstore;
use OpenDxp\Model\DataObject\Csstore;
use OpenDxp\Model\DataObject\Data\QuantityValue;
use OpenDxp\Tests\Factory\CsstoreFactory;
use OpenDxp\Tool;

function reloaded(Csstore $object): Classificationstore
{
    return Csstore::getById($object->getId(), ['force' => true])->getCsstore();
}

it('keeps the value a key of a group was given', function (string $group, string $key, Closure $value) {

    $expected = $value();
    $object = CsstoreFactory::createOne();
    $groupId = storeGroup($group)->getId();
    $keyId = storeKey($key)->getId();

    $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, $expected);
    $object->save();

    expect(reloaded($object)->getLocalizedKeyValue($groupId, $keyId))->toEqual($expected);
})->with('classification key values');

it('keeps the value of a key apart per language', function () {

    $object = CsstoreFactory::createOne();
    $groupId = storeGroup('testgroup2')->getId();
    $keys = ['input' => storeKey('input')->getId(), 'select' => storeKey('select')->getId()];
    $languages = [...Tool::getValidLanguages(), 'default'];

    $written = 0;

    foreach ($languages as $language) {
        foreach ($keys as $keyId) {
            $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, ++$written, $language);
        }
    }

    $object->save();
    $store = reloaded($object);
    $read = 0;

    foreach ($languages as $language) {
        foreach ($keys as $name => $keyId) {
            expect($store->getLocalizedKeyValue($groupId, $keyId, $language))
                ->toEqual(++$read, sprintf('%s in %s', $name, $language));
        }
    }
});

it('reaches the value of the default language for a language that holds none', function () {

    $object = CsstoreFactory::createOne();
    $groupId = storeGroup('testgroup2')->getId();
    $keyId = storeKey('input')->getId();

    $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, null, 'en');
    $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'the default value', 'default');
    $object->save();

    expect(reloaded($object)->getLocalizedKeyValue($groupId, $keyId, 'en'))->toBe('the default value');
});

it('holds no quantity at all once both its value and its unit were taken away', function () {

    $object = CsstoreFactory::createOne();
    $groupId = storeGroup('testgroupQvalue')->getId();
    $keyId = storeKey('qValue')->getId();
    $unit = aUnit('cm')->getId();

    $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, new QuantityValue(123, $unit));
    $object->save();

    expect(reloaded($object)->getLocalizedKeyValue($groupId, $keyId)->getValue())->toEqual(123);

    $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, new QuantityValue(null, $unit));
    $object->save();

    expect(reloaded($object)->getLocalizedKeyValue($groupId, $keyId)->getValue())->toBeNull();

    $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, new QuantityValue(null, null));
    $object->save();

    expect(reloaded($object)->getLocalizedKeyValue($groupId, $keyId))->toBeNull();
});

it('holds the groups and the keys the store was installed with', function () {
    expect(theStore())
        ->not->toBeNull()
        ->and(storeGroup('testgroup1'))
        ->not->toBeNull()
        ->and(keysOfGroup('testgroup1'))
        ->toHaveCount(3)
        ->and(keysOfGroup('testgroup2'))
        ->toHaveCount(19);
});
