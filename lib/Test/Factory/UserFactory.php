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
 * @extends AbstractUserRoleFactory<User>
 */
final class UserFactory extends AbstractUserRoleFactory
{
    public static function class(): string
    {
        return User::class;
    }

    public function admin(): static
    {
        return $this->with(['admin' => true]);
    }

    public function withRoles(User\Role ...$roles): static
    {
        return $this->with([
            'roles' => array_map(
                static fn (User\Role $role): ?int => $role->getId(),
                $roles,
            ),
        ]);
    }

    public function withPassword(string $password): static
    {
        return $this->with(['password' => $password]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'name'     => self::faker()->unique()->userName(),
            // OpenDXP refuses to sign in a user without a password, even through a token.
            'password' => self::faker()->password(),
        ];
    }

    /**
     * A test names the password in plain text. OpenDXP stores only its hash.
     */
    protected function initialize(): static
    {
        return parent::initialize()
            ->beforeInstantiate(
                static function (array $parameters): array {
                    $parameters['password'] = Authentication::getPasswordHash(
                        $parameters['name'],
                        $parameters['password'],
                    );

                    return $parameters;
                },
            );
    }
}
