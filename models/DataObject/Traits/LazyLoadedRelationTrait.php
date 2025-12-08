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

namespace OpenDxp\Model\DataObject\Traits;

use OpenDxp\Model\DataObject\LazyLoadedFieldsInterface;

/**
 * @internal
 */
trait LazyLoadedRelationTrait
{
    protected array $loadedLazyKeys = [];

    public function markLazyKeyAsLoaded(string $key): void
    {
        $this->loadedLazyKeys[$key] = 1;
    }

    public function unmarkLazyKeyAsLoaded(string $key): void
    {
        unset($this->loadedLazyKeys[$key]);
    }

    public function isLazyKeyLoaded(string $key): bool
    {
        if ($this->isAllLazyKeysMarkedAsLoaded()) {
            return true;
        }

        return isset($this->loadedLazyKeys[$key]);
    }

    public function buildLazyKey(string $name, string $language): string
    {
        return $name . LazyLoadedFieldsInterface::LAZY_KEY_SEPARATOR . $language;
    }
}
