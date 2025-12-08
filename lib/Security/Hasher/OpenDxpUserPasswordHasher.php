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

namespace OpenDxp\Security\Hasher;

use OpenDxp\Security\User\User;
use OpenDxp\Tool\Authentication;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * As opendxp needs the user information when hashing the password, every user gets his own hasher instance with a
 * user object. If user is no opendxp user, fall back to default implementation.
 *
 * @method User getUser()
 *
 * @internal
 */
class OpenDxpUserPasswordHasher extends AbstractUserAwarePasswordHasher
{
    use CheckPasswordLengthTrait;

    #[\Override]
    public function hash(string $plainPassword, ?string $salt = null): string
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            throw new BadCredentialsException(sprintf('Password exceeds a maximum of %d characters', static::MAX_PASSWORD_LENGTH));
        }

        return Authentication::getPasswordHash($this->getUser()->getUserIdentifier(), $plainPassword);
    }

    #[\Override]
    public function verify(string $hashedPassword, string $plainPassword, ?string $salt = null): bool
    {
        if ($this->isPasswordTooLong($hashedPassword)) {
            return false;
        }

        return Authentication::verifyPassword($this->getUser()->getUser(), $plainPassword);
    }
}
