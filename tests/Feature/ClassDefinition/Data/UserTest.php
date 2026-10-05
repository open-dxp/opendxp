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

const DEFINITION = [
    'name' => 'openDxpUser',
    'title' => 'OpenDxp User',
    'tooltip' => '',
    'mandatory' => false,
    'noteditable' => false,
    'index' => false,
    'locked' => false,
    'style' => '',
    'permissions' => null,
    'datatype' => 'data',
    'fieldtype' => 'user',
    'relationType' => false,
    'invisible' => false,
    'visibleGridView' => false,
    'visibleSearch' => false,
    'blockedVarsForExport' => [],
    'options' => null,
    'width' => '',
    'defaultValue' => null,
    'optionsProviderClass' => null,
    'optionsProviderData' => null,
    'columnLength' => 190,
    'dynamicOptions' => false,
    'defaultValueGenerator' => '',
    'unique' => false,
];

beforeEach(fn () => $this->wasInAdmin = OpenDxp::inAdmin());

afterEach(function () {
    $this->wasInAdmin ? OpenDxp::setAdminMode() : OpenDxp::unsetAdminMode();
});

it('offers every user to pick from while the admin is being served', function () {

    $user = UserFactory::createOne();
    OpenDxp::setAdminMode();

    $options = User::__set_state(DEFINITION)->getOptions();

    expect(array_column($options, 'value'))->toContain($user->getId());
});

it('offers nothing to pick from outside the admin', function () {

    UserFactory::createOne();
    OpenDxp::unsetAdminMode();

    expect(User::__set_state(DEFINITION)->getOptions())->toBeEmpty();
});
