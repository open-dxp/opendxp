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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Security\Hasher\Factory;

use OpenDxp\Security\Exception\ConfigurationException;
use OpenDxp\Security\Hasher\UserAwarePasswordHasherInterface;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 *
 * Password Hasher factory keeping a dedicated hasher instance per user object. This is needed as OpenDxp Users and user
 * objects containing Password field definitions handle their encoding logic by themself. The user aware hasher
 * delegates encoding and verification to the user object.
 *
 * Example DI configuration for a factory building PasswordFieldHasher instances which get 'password' as argument:
 *
 *      website_demo.security.password_hasher_factory:
 *          class: OpenDxp\Security\Hasher\Factory\UserAwarePasswordHasherFactory
 *          arguments:
 *              - OpenDxp\Security\Hasher\PasswordFieldHasher
 *              - ['password']
 */
class UserAwarePasswordHasherFactory extends AbstractHasherFactory
{
    /**
     * @var PasswordHasherInterface[]
     */
    private array $hashers = [];

    public function getPasswordHasher(string|PasswordAuthenticatedUserInterface|PasswordHasherAwareInterface $user): PasswordHasherInterface
    {
        if (!$user instanceof UserInterface) {
            throw new RuntimeException(sprintf(
                'Need an instance of UserInterface to build a password hasher, "%s" given',
                get_debug_type($user)
            ));
        }

        $userIdentifier = $user->getUserIdentifier();

        if (isset($this->hashers[$userIdentifier])) {
            return $this->hashers[$userIdentifier];
        }

        $reflector = $this->getReflector();
        if (!$reflector->implementsInterface(UserAwarePasswordHasherInterface::class)) {
            throw new ConfigurationException('A password hasher built by the UserAwarePasswordHasherFactory must implement UserAwarePasswordHasherInterface');
        }

        $hasher = $this->buildPasswordHasher($reflector);

        if ($hasher instanceof UserAwarePasswordHasherInterface) {
            $hasher->setUser($user);
        }

        $this->hashers[$userIdentifier] = $hasher;

        return $hasher;
    }
}
