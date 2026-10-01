<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use OpenDxp;
use OpenDxp\Model\DataObject\ClassDefinition\Data\User;
use OpenDxp\Tests\Support\Test\TestCase;

class UserTest extends TestCase
{
    private const array SAMPLE_USER_DATA = [
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

    private bool $inAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inAdmin = OpenDxp::inAdmin();
    }

    protected function tearDown(): void
    {
        if ($this->inAdmin) {
            OpenDxp::setAdminMode();
        } else {
            OpenDxp::unsetAdminMode();
        }

        parent::tearDown();
    }

    public function test__set_stateDoesNotPopulateSelectOptionsWhenNotInAdminMode(): void
    {
        OpenDxp::unsetAdminMode();

        $user = User::__set_state(self::SAMPLE_USER_DATA);

        $this->assertEmpty($user->getOptions());
    }

    public function test__set_statePopulatesSelectOptionsIbAdminMode(): void
    {
        OpenDxp::setAdminMode();

        $user = User::__set_state(self::SAMPLE_USER_DATA);

        $this->assertNotEmpty($user->getOptions());
    }
}
