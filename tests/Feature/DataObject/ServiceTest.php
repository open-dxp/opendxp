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

use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

it('finds an object that names the user in only one of its user fields', function (string $field) {
    $user = UserFactory::createOne();
    $object = TestFieldTypesFactory::createOne([$field => (string) $user->getId()]);

    $referencing = Service::getObjectsReferencingUser($user->getId());

    expect(array_map(static fn (Concrete $found) => $found->getId(), $referencing))->toBe([$object->getId()]);
})->with([
    'user',
    'user2',
]);
