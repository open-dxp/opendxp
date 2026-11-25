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

namespace OpenDxp\Tests\Feature\User;

use OpenDxp\Model\User\AbstractUser;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;

it('reads hasChildren of users and roles through the property accessor', function (AbstractUser $user) {
    $accessor = PropertyAccess::createPropertyAccessor();

    expect($accessor->getValue($user, 'hasChildren'))->toBeFalse();
})->with([
    'user' => fn () => UserFactory::createOne(),
    'role' => fn () => UserRoleFactory::createOne(),
]);
