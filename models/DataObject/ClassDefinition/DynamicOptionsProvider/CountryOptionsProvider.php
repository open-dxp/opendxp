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

use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Country;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Countrymultiselect;

class CountryOptionsProvider implements SelectOptionsProviderInterface
{
    public function __construct(private readonly LocaleServiceInterface $localeService)
    {
    }

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $countries = $this->localeService->getDisplayRegions();
        asort($countries);
        $options = [];
        $restrictTo = null;

        if ($fieldDefinition instanceof Country || $fieldDefinition instanceof Countrymultiselect) {
            $restrictTo = $fieldDefinition->getRestrictTo();
            if ($restrictTo) {
                $restrictTo = explode(',', $restrictTo);
            }
        }

        foreach ($countries as $short => $translation) {
            if (strlen($short) === 2) {
                if ($restrictTo && !in_array($short, $restrictTo)) {
                    continue;
                }
                $options[] = [
                    'key' => $translation,
                    'value' => $short,
                ];
            }
        }

        return $options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): ?string
    {
        if ($fieldDefinition instanceof Country) {
            return $fieldDefinition->getDefaultValue();
        }

        return null;
    }
}
