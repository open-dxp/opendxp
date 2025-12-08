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

namespace OpenDxp\Session\Attribute;

use OpenDxp\Session\Attribute\Exception\AttributeBagLockedException;
use Override;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class LockableAttributeBag extends AttributeBag implements LockableAttributeBagInterface
{
    protected bool $locked = false;

    public function lock(): void
    {
        $this->locked = true;
    }

    public function unlock(): void
    {
        $this->locked = false;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    #[Override]
    public function set(string $name, mixed $value): void
    {
        $this->checkLock();

        parent::set($name, $value);
    }

    #[Override]
    public function replace(array $attributes): void
    {
        $this->checkLock();

        parent::replace($attributes);
    }

    #[Override]
    public function remove(string $name): mixed
    {
        $this->checkLock();

        return parent::remove($name);
    }

    #[Override]
    public function clear(): mixed
    {
        $this->checkLock();

        return parent::clear();
    }

    /**
     * @throws AttributeBagLockedException
     *      if lock is set
     */
    protected function checkLock(): void
    {
        if ($this->locked) {
            throw new AttributeBagLockedException('Attribute bag is locked');
        }
    }
}
