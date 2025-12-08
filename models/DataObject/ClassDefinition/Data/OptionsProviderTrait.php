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

namespace OpenDxp\Model\DataObject\ClassDefinition\Data;

/**
 * @see OptionsProviderInterface
 */
trait OptionsProviderTrait
{
    public ?string $optionsProviderType = null;

    public ?string $optionsProviderClass = null;

    public ?string $optionsProviderData = null;

    public function getOptionsProviderType(): ?string
    {
        return $this->optionsProviderType;
    }

    public function setOptionsProviderType(?string $optionsProviderType): void
    {
        $this->optionsProviderType = $optionsProviderType;
    }

    public function getOptionsProviderClass(): ?string
    {
        return $this->optionsProviderClass;
    }

    public function setOptionsProviderClass(?string $optionsProviderClass): void
    {
        $this->optionsProviderClass = $optionsProviderClass;
    }

    public function getOptionsProviderData(): ?string
    {
        return $this->optionsProviderData;
    }

    public function setOptionsProviderData(?string $optionsProviderData): void
    {
        $this->optionsProviderData = $optionsProviderData;
    }

    public function useConfiguredOptions(): bool
    {
        if ($this->getOptionsProviderType() === OptionsProviderInterface::TYPE_CONFIGURE) {
            return true;
        }
        return $this->getOptionsProviderType() === null && empty($this->getOptionsProviderClass());
    }
}
