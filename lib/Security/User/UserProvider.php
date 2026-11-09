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

namespace OpenDxp\Security\User;

use OpenDxp\Model\User as OpenDxpUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $openDxpUser = OpenDxpUser::getByName($identifier);

        if ($openDxpUser) {
            return $this->buildUser($openDxpUser);
        }

        throw new UserNotFoundException(sprintf('User %s was not found', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            // user is not supported - we only support opendxp users
            throw new UnsupportedUserException();
        }

        $refreshedOpenDxpUser = OpenDxpUser::getById($user->getId());

        if (!$refreshedOpenDxpUser instanceof OpenDxpUser) {
            throw new UserNotFoundException(sprintf('User %d was not found', $user->getId()));
        }

        return $this->buildUser($refreshedOpenDxpUser);
    }

    protected function buildUser(OpenDxpUser $openDxpUser): User
    {
        return new User($openDxpUser);
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
