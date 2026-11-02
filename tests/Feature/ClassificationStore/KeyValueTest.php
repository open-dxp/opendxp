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

use Carbon\Carbon;
use Closure;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Input;
use OpenDxp\Model\DataObject\Data;
use OpenDxp\Test\Elements;
use OpenDxp\Tests\Factory\CsstoreFactory;

it('keeps the value a key of a group was given', function (string $group, string $key, Closure $value) {
    $expected = $value();

    $object = CsstoreFactory::new()
        ->withClassificationValues(
            'csstore',
            storeGroup($group),
            [$key => $expected],
        )
        ->create();
    $stored = Elements::refresh($object)->getCsstore()->getLocalizedKeyValue(
        storeGroup($group)->getId(),
        storeKey($key)->getId(),
    );

    expect($stored)->toEqual($expected);
})->with([
    'a date' => [
        'dates',
        'date',
        fn () => Carbon::createFromTimestamp(1700000000),
    ],
    'a date and a time' => [
        'dates',
        'datetime',
        fn () => Carbon::createFromTimestamp(1700000000),
    ],
    'an encrypted text' => [
        'dates',
        'encryptedField',
        fn () => new Data\EncryptedField(new Input(), 'abc'),
    ],
    'a line of text' => [
        'values',
        'input',
        fn () => 'abc',
    ],
    'a colour' => [
        'values',
        'rgbaColor',
        fn () => new Data\RgbaColor(1, 2, 3, 4),
    ],
    'a select' => [
        'values',
        'select',
        fn () => 'B',
    ],
    'a time of day' => [
        'values',
        'time',
        fn () => '12:30',
    ],
    'a number' => [
        'values',
        'numeric',
        fn () => 12.57,
    ],
    'a boolean select' => [
        'values',
        'booleanSelect',
        fn () => true,
    ],
    'a user' => [
        'values',
        'user',
        fn () => user('unittestdatauser1')->getId(),
    ],
    'a text area' => [
        'values',
        'textarea',
        fn () => "line1\nline2",
    ],
    'formatted text' => [
        'values',
        'wysiwyg',
        fn () => 'line1<br />line2',
    ],
    'a checkbox' => [
        'values',
        'checkbox',
        fn () => true,
    ],
    'a slider' => [
        'values',
        'slider',
        fn () => 47,
    ],
    'a table' => [
        'values',
        'table',
        fn () => [
            [
                'A',
                'B',
            ],
            [
                'C',
                'D',
            ],
        ],
    ],
    'a country' => [
        'values',
        'country',
        fn () => 'AT',
    ],
    'a language' => [
        'values',
        'language',
        fn () => 'fr',
    ],
    'several selected values' => [
        'values',
        'multiselect',
        fn () => [
            'A',
            'D',
        ],
    ],
    'several countries' => [
        'values',
        'countrymultiselect',
        fn () => [
            'AT',
            'DE',
        ],
    ],
    'several languages' => [
        'values',
        'languagemultiselect',
        fn () => [
            'de',
            'fr',
        ],
    ],
    'a quantity' => [
        'values',
        'quantityValue',
        fn () => new Data\QuantityValue(123, quantityUnit('cm')->getId()),
    ],
    'a quantity written as text' => [
        'values',
        'inputQuantityValue',
        fn () => new Data\InputQuantityValue('abc', quantityUnit('cm')->getId()),
    ],
]);

it('keeps the value of a key apart per language', function () {
    $groupId = storeGroup('values')->getId();
    $keyId = storeKey('input')->getId();

    $object = CsstoreFactory::new()
        ->withLocalizedClassificationValues(
            'csstore',
            storeGroup('values'),
            'input',
            [
                'en' => 'English',
                'de' => 'German',
                'default' => 'Default',
            ],
        )
        ->create();
    $store = Elements::refresh($object)->getCsstore();

    expect($store->getLocalizedKeyValue($groupId, $keyId, 'en'))
        ->toBe('English')
        ->and($store->getLocalizedKeyValue($groupId, $keyId, 'de'))
        ->toBe('German')
        ->and($store->getLocalizedKeyValue($groupId, $keyId, 'default'))
        ->toBe('Default');
});

it('returns the value of the default language for a language that holds none', function () {
    $object = CsstoreFactory::new()
        ->withLocalizedClassificationValues(
            'csstore',
            storeGroup('values'),
            'input',
            [
                'en' => null,
                'default' => 'the default value',
            ],
        )
        ->create();

    $value = Elements::refresh($object)->getCsstore()->getLocalizedKeyValue(
        storeGroup('values')->getId(),
        storeKey('input')->getId(),
        'en',
    );

    expect($value)->toBe('the default value');
});

describe('a quantity', function () {
    beforeEach(function () {
        $this->unitId = quantityUnit('cm')->getId();
        $this->groupId = storeGroup('quantities')->getId();
        $this->keyId = storeKey('qValue')->getId();
        $this->object = CsstoreFactory::new()
            ->withClassificationValues(
                'csstore',
                storeGroup('quantities'),
                ['qValue' => new Data\QuantityValue(123, $this->unitId)],
            )
            ->create();
    });

    it('keeps its unit when its value is taken away', function () {
        $this->object->getCsstore()->setLocalizedKeyValue(
            $this->groupId,
            $this->keyId,
            new Data\QuantityValue(null, $this->unitId),
        );
        $this->object->save();

        $quantity = Elements::refresh($this->object)->getCsstore()->getLocalizedKeyValue($this->groupId, $this->keyId);

        expect($quantity)
            ->getValue()
            ->toBeNull()
            ->getUnitId()
            ->toBe($this->unitId);
    });

    it('is gone once its value and its unit are taken away', function () {
        $this->object->getCsstore()->setLocalizedKeyValue(
            $this->groupId,
            $this->keyId,
            new Data\QuantityValue(null, null),
        );
        $this->object->save();

        $quantity = Elements::refresh($this->object)->getCsstore()->getLocalizedKeyValue($this->groupId, $this->keyId);

        expect($quantity)->toBeNull();
    });
});
