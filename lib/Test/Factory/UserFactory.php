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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\User;
use OpenDxp\Tool\Authentication;

/**
 * @extends AbstractSavingFactory<User>
 *
 * @method User create(array|callable $attributes = [])
 * @method static User createOne(array $attributes = [])
 * @method static list<User> createMany(int $number, array $attributes = [])
 */
final class UserFactory extends AbstractSavingFactory
{
    public const string PASSWORD = 'test-password';

    public static function class(): string
    {
        return User::class;
    }

    public function admin(): static
    {
        return $this->with(['admin' => true]);
    }

    public function withPermissions(string ...$permissions): static
    {
        return $this->with(['permissions' => $permissions]);
    }

    protected function defaults(): array
    {
        return [
            'name'     => sprintf('user-%s', uniqid()),
            'admin'    => false,
            'parentId' => 0,
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()->afterInstantiate(
            static function (User $user): void {
                $user->setPassword(Authentication::getPasswordHash($user->getName(), self::PASSWORD));
            },
        );
    }
}
