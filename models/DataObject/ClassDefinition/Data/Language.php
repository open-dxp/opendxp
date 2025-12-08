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

use OpenDxp;
use OpenDxp\Model;
use OpenDxp\Model\DataObject\ClassDefinition\Service;
use OpenDxp\Tool;
use Override;

class Language extends Model\DataObject\ClassDefinition\Data\Select
{
    /**
     * @internal
     */
    public bool $onlySystemLanguages = false;

    /**
     * @internal
     */
    public function configureOptions(): void
    {
        $validLanguages = Tool::getValidLanguages();
        $locales = Tool::getSupportedLocales();
        $options = [];

        foreach ($locales as $short => $translation) {
            if ($this->getOnlySystemLanguages() && !in_array($short, $validLanguages)) {
                continue;
            }

            $options[] = [
                'key' => $translation,
                'value' => $short,
            ];
        }

        $this->setOptions($options);
    }

    public function getOnlySystemLanguages(): bool
    {
        return $this->onlySystemLanguages;
    }

    /**
     * @return $this
     */
    public function setOnlySystemLanguages(bool|int $value): static
    {
        $this->onlySystemLanguages = (bool) $value;

        return $this;
    }

    public static function __set_state(array $data): static
    {
        $obj = parent::__set_state($data);

        if (OpenDxp::inAdmin()) {
            $obj->configureOptions();
        }

        return $obj;
    }

    #[Override]
    public function jsonSerialize(): mixed
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return parent::jsonSerialize();
    }

    #[Override]
    public function resolveBlockedVars(): array
    {
        $blockedVars = parent::resolveBlockedVars();
        $blockedVars[] = 'options';

        return $blockedVars;
    }

    #[Override]
    public function isFilterable(): bool
    {
        return true;
    }

    #[Override]
    public function getFieldType(): string
    {
        return 'language';
    }
}
