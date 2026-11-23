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

namespace OpenDxp\Tests\Feature\DataType;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Data\RgbaColor;
use OpenDxp\Model\DataObject\TestFieldTypes;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

/**
 * @return list<int>
 */
function idsFilteredBy(string $field, mixed $value, string $operator): array
{
    $condition = ClassDefinition::getByName('TestFieldTypes')->getFieldDefinition($field)->getFilterConditionExt($value, $operator, ['name' => $field]);

    return TestFieldTypes::getList(['condition' => $condition])->loadIdList();
}

it('matches an underscore literally in text filters', function () {
    $match = TestFieldTypesFactory::createOne(['input' => 'a_b']);
    TestFieldTypesFactory::createOne(['input' => 'axb']);

    expect(idsFilteredBy('input', 'a_b', 'LIKE'))->toBe([$match->getId()]);
});

it('matches an underscore literally in select filters', function () {
    $match = TestFieldTypesFactory::createOne(['select' => 'a_b']);
    TestFieldTypesFactory::createOne(['select' => 'axb']);

    expect(idsFilteredBy('select', 'a_b', 'LIKE'))->toBe([$match->getId()]);
});

it('matches an underscore literally in multiselect filters', function () {
    $match = TestFieldTypesFactory::createOne(['multiselect' => ['a_b']]);
    TestFieldTypesFactory::createOne(['multiselect' => ['axb']]);

    expect(idsFilteredBy('multiselect', 'a_b', '='))->toBe([$match->getId()]);
});

it('matches an underscore literally in color filters', function () {
    TestFieldTypesFactory::createOne(['rgbaColor' => new RgbaColor(255, 0, 0, 255)]);

    expect(idsFilteredBy('rgbaColor', 'f_0', 'LIKE'))->toBe([]);
});
