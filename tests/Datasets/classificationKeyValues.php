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

use Carbon\Carbon;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Input;
use OpenDxp\Model\DataObject\Data;

/**
 * A classification store holds a value per group and key, so each entry names both.
 */
dataset('classification key values', [
    'a date' => ['testgroup1', 'date', fn () => Carbon::createFromTimestamp(1700000000)],
    'a date and a time' => ['testgroup1', 'datetime', fn () => Carbon::createFromTimestamp(1700000000)],
    'an encrypted text' => ['testgroup1', 'encryptedField', fn () => new Data\EncryptedField(new Input(), 'abc')],
    'a line of text' => ['testgroup2', 'input', fn () => 'abc'],
    'a colour' => ['testgroup2', 'rgbaColor', fn () => new Data\RgbaColor(1, 2, 3, 4)],
    'a select' => ['testgroup2', 'select', fn () => 'B'],
    'a time of day' => ['testgroup2', 'time', fn () => '12:30'],
    'a number' => ['testgroup2', 'numeric', fn () => 12.57],
    'a boolean select' => ['testgroup2', 'booleanSelect', fn () => true],
    'a user' => ['testgroup2', 'user', fn () => aUser('unittestdatauser1')->getId()],
    'a text area' => ['testgroup2', 'textarea', fn () => "line1\nline2"],
    'formatted text' => ['testgroup2', 'wysiwyg', fn () => 'line1<br />line2'],
    'a checkbox' => ['testgroup2', 'checkbox', fn () => true],
    'a slider' => ['testgroup2', 'slider', fn () => 47],
    'a table' => ['testgroup2', 'table', fn () => [['A', 'B'], ['C', 'D']]],
    'a country' => ['testgroup2', 'country', fn () => 'AT'],
    'a language' => ['testgroup2', 'language', fn () => 'fr'],
    'several selected values' => ['testgroup2', 'multiselect', fn () => ['A', 'D']],
    'several countries' => ['testgroup2', 'countrymultiselect', fn () => ['AT', 'DE']],
    'several languages' => ['testgroup2', 'languagemultiselect', fn () => ['AT', 'DE']],
    'a quantity' => ['testgroup2', 'quantityValue', fn () => new Data\QuantityValue(123, aUnit('cm')->getId())],
    'a quantity written as text' => [
        'testgroup2',
        'inputQuantityValue',
        fn () => new Data\InputQuantityValue('abc', aUnit('cm')->getId()),
    ],
]);
