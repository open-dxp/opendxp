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

namespace OpenDxp\Tests\Feature\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition;

it('returns null for an id no class carries', function () {
    $class = ClassDefinition::getByIdIgnoreCase('-9999');

    expect($class)->toBeNull();
});

it('finds a class under an id that differs only in case', function () {
    $class = ClassDefinition::getByIdIgnoreCase('Inheritance');

    expect($class)
        ->getName()
        ->toBe('inheritance');
});
