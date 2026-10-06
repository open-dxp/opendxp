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

it('writes the setter code a field needs', function (ClassDefinition\Data $definition) {
    $class = ClassDefinition::getByName('unittest');
    $expected = file_get_contents(fixture(sprintf('setters/%s.txt', $definition->getName())));

    $code = $definition->getSetterCode($class);

    expect($code)->toBe($expected);
})->with([
    'a line of text' => fn () => ClassDefinition::getByName('unittest')->getFieldDefinition('input'),
    'a field collection' => fn () => ClassDefinition::getByName('unittest')->getFieldDefinition('fieldcollection'),
    'object bricks' => fn () => ClassDefinition::getByName('unittest')->getFieldDefinition('mybricks'),
    'a quantity' => fn () => ClassDefinition::getByName('unittest')->getFieldDefinition('quantityValue'),
    'a localized line of text' => fn () => ClassDefinition::getByName('unittest')
        ->getFieldDefinition('localizedfields')
        ->getFieldDefinition('linput'),
]);
