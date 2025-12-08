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

namespace OpenDxp\Bundle\CoreBundle\OptionsProvider;

use Exception;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use OpenDxp\Model\DataObject\SelectOptions\Config;
use OpenDxp\Model\DataObject\SelectOptions\Data\SelectOption;

class SelectOptionsOptionsProvider implements SelectOptionsProviderInterface
{
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        if (!$fieldDefinition instanceof Data\OptionsProviderInterface) {
            return [];
        }

        $configurationId = $fieldDefinition->getOptionsProviderData();
        $selectOptionsConfiguration = Config::getById($configurationId);
        if (!$selectOptionsConfiguration instanceof \OpenDxp\Model\DataObject\SelectOptions\Config) {
            throw new Exception('Missing select options configuration ' . $configurationId, 1677137682677);
        }

        return array_map(
            fn (SelectOption $selectOption) => [
                'value' => $selectOption->getValue(),
                'key' => $selectOption->getLabel(),
            ],
            $selectOptionsConfiguration->getSelectOptions(),
        );
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): ?string
    {
        if ($fieldDefinition instanceof Data\Select) {
            return $fieldDefinition->getDefaultValue();
        }

        return null;
    }
}
