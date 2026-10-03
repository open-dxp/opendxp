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


namespace OpenDxp\Tests\Feature\DataObject;

use OpenDxp\Model\DataObject\ClassDefinition;

function fieldOf(string $name, bool $localized): ClassDefinition\Data
{
    $class = ClassDefinition::getByName('unittest');

    return $localized
        ? $class->getFieldDefinition('localizedfields')->getFieldDefinition($name)
        : $class->getFieldDefinition($name);
}

it('writes the setter a field needs', function (string $field, bool $localized) {

    $written = fieldOf($field, $localized)->getSetterCode(ClassDefinition::getByName('unittest'));

    expect($written)->toBe(file_get_contents(fixture('setters/' . $field . '.txt')));
})->with([
    'a line of text' => ['input', false],
    'a field collection' => ['fieldcollection', false],
    'object bricks' => ['mybricks', false],
    'a quantity' => ['quantityValue', false],
    'a localized line of text' => ['linput', true],
]);
