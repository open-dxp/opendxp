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

use OpenDxp\Model;
use OpenDxp\Model\DataObject\ClassDefinition\Service;
use Override;

class Gender extends Model\DataObject\ClassDefinition\Data\Select
{
    public function configureOptions(): void
    {
        $options = [
            ['key' => 'male', 'value' => 'male'],
            ['key' => 'female', 'value' => 'female'],
            ['key' => 'other', 'value' => 'other'],
            ['key' => 'unknown', 'value' => 'unknown'],
        ];

        $this->setOptions($options);
    }

    public static function __set_state(array $data): static
    {
        $obj = parent::__set_state($data);
        $obj->configureOptions();

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
    public function getFieldType(): string
    {
        return 'gender';
    }
}
