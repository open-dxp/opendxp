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

namespace OpenDxp\Security\Hasher;

use OpenDxp\Model\DataObject\ClassDefinition\Data\Password;
use OpenDxp\Model\DataObject\Concrete;
use Override;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * @internal
 *
 * @method Concrete getUser()
 */
class PasswordFieldHasher extends AbstractUserAwarePasswordHasher
{
    use CheckPasswordLengthTrait;

    /**
     * If true, the user password hash will be updated if necessary.
     *
     */
    protected bool $updateHash = true;

    public function __construct(protected string $fieldName = 'password')
    {
    }

    public function getUpdateHash(): bool
    {
        return $this->updateHash;
    }

    public function setUpdateHash(bool $updateHash): void
    {
        $this->updateHash = $updateHash;
    }

    public function hashPassword(string $raw, ?string $salt): string
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException(sprintf('Password exceeds a maximum of %d characters', static::MAX_PASSWORD_LENGTH));
        }

        return $this->getFieldDefinition()->calculateHash($raw);
    }

    public function isPasswordValid(string $encoded, string $raw): bool
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        return $this->getFieldDefinition()->verifyPassword($raw, $this->getUser(), $this->updateHash);
    }

    /**
     *
     * @throws RuntimeException
     */
    protected function getFieldDefinition(): Password
    {
        $field = $this->getUser()->getClass()->getFieldDefinition($this->fieldName);

        if (!$field instanceof Password) {
            throw new RuntimeException(sprintf(
                'Field %s for user type %s is expected to be of type %s, %s given',
                $this->fieldName,
                $this->user instanceof \Symfony\Component\Security\Core\User\UserInterface ? $this->user::class : self::class,
                Password::class,
                get_debug_type($field)
            ));
        }

        return $field;
    }

    #[Override]
    public function verify(string $hashedPassword, string $plainPassword, ?string $salt = null): bool
    {
        return $this->getFieldDefinition()->verifyPassword($plainPassword, $this->getUser(), $this->updateHash);
    }
}
