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

namespace OpenDxp\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait SelectionProviderTrait
{
    protected function doEnrichDefinitionDefinition(?DataObject\Concrete $object, string $fieldname, string $purpose, int $mode, array $context = []): void
    {
        if ($this->getOptionsProviderType() === Data\OptionsProviderInterface::TYPE_CONFIGURE) {
            return;
        }

        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            $mode
        );
        if ($optionsProvider) {
            $context['object'] ??= $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $fieldname;
            if (!isset($context['purpose'])) {
                $context['purpose'] = $purpose;
            }

            $options = DataObject\Service::useInheritedValues(
                true,
                fn () => $optionsProvider->getOptions($context, $this)
            );

            $this->setOptions($options);

            $defaultValue = $optionsProvider->getDefaultValue($context, $this);
            $this->setDefaultValue($defaultValue);

            $hasStaticOptions = $optionsProvider->hasStaticOptions($context, $this);
            $this->dynamicOptions = !$hasStaticOptions;
        }
    }
}
