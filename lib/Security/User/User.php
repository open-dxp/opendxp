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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Security\User;

use OpenDxp;
use OpenDxp\Model\User as OpenDxpUser;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Proxy user to opendxp model and expose roles as ROLE_* array. If we can safely change the roles on the user model
 * this proxy can be removed and the UserInterface can directly be implemented on the model.
 */
class User implements UserInterface, EquatableInterface, GoogleTwoFactorInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(protected OpenDxpUser $user)
    {
    }

    public function getId(): int
    {
        return $this->user->getId();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getName() ?? '';
    }

    public function getUser(): OpenDxpUser
    {
        return $this->user;
    }

    public function getRoles(): array
    {
        $roles = [];

        $roles[] = $this->user->isAdmin() ? 'ROLE_OPENDXP_ADMIN' : 'ROLE_OPENDXP_USER';

        foreach ($this->user->getRoles() as $roleId) {
            if ($role = OpenDxpUser\Role::getById($roleId)) {
                $roles[] = 'ROLE_' . strtoupper($role->getName());
            }
        }

        return $roles;
    }

    public function getPassword(): ?string
    {
        return $this->user->getPassword();
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
        // TODO: anything to do here?
    }

    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof self && $user->getId() === $this->getId();
    }

    /**
     * Return true if the user should do two-factor authentication.
     *
     */
    public function isGoogleAuthenticatorEnabled(): bool
    {
        return (bool) $this->user->getTwoFactorAuthentication('enabled');
    }

    /**
     * Return the user name.
     *
     */
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->user->getName();
    }

    /**
     * Return the Google Authenticator secret
     * When an empty string or null is returned, the Google authentication is disabled.
     *
     */
    public function getGoogleAuthenticatorSecret(): ?string
    {
        if ($this->isGoogleAuthenticatorEnabled()) {
            $secret = $this->user->getTwoFactorAuthentication('secret');
            if (!$secret) {
                // we return a dummy token
                $twoFactorService = OpenDxp::getContainer()->get('scheb_two_factor.security.google_authenticator');

                return $twoFactorService->generateSecret();
            }

            return $secret;
        }

        return null;
    }
}
