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

namespace OpenDxp\Tests\Feature\ClassDefinition\Data;

use OpenDxp;
use OpenDxp\Model\DataObject\ClassDefinition\Data\User;
use OpenDxp\Test\Factory\UserFactory;

it('offers every user to pick from when it is loaded in admin mode', function () {
    $user = UserFactory::createOne();
    OpenDxp::setAdminMode();

    $field = User::__set_state(['name' => 'openDxpUser']);

    $values = array_column($field->getOptions(), 'value');
    expect($values)->toContain($user->getId());
});

it('offers nothing to pick from when it is loaded outside of admin mode', function () {
    UserFactory::createOne();

    $field = User::__set_state(['name' => 'openDxpUser']);

    expect($field->getOptions())->toBeEmpty();
});
