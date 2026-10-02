<?php

declare(strict_types=1);

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

    protected function defaults(): array
    {
        return [
            'name'     => sprintf('user-%s', self::faker()->unique()->numerify('##########')),
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
