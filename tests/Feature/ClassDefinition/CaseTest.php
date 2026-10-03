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

it('hands back nothing for an id no class carries', function () {
    expect(ClassDefinition::getByIdIgnoreCase('-9999'))->toBeNull();
});

it('finds a class under an id that differs only in case', function () {
    expect(ClassDefinition::getByIdIgnoreCase('Inheritance'))->not->toBeNull();
});
