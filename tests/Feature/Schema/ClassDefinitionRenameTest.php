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

use OpenDxp\Model\DataObject\ClassDefinition;

it('is found under the name it was renamed to', function () {

    ClassDefinition::getByName('unittest')->rename('unittest_renamed');

    expect(ClassDefinition::getByName('unittest'))
        ->toBeNull()
        ->and(ClassDefinition::getByName('unittest_renamed'))
        ->toBeInstanceOf(ClassDefinition::class);

    ClassDefinition::getByName('unittest_renamed')->rename('unittest');

    expect(ClassDefinition::getByName('unittest'))->toBeInstanceOf(ClassDefinition::class);
});
