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

namespace OpenDxp\Model\DataObject\QuantityValue;

use Exception;
use OpenDxp\Model\DataObject\ClassDefinition\Helper\UnitConverterResolver;
use OpenDxp\Model\DataObject\Data\AbstractQuantityValue;

class UnitConversionService
{
    public function __construct(protected QuantityValueConverterInterface $defaultConverter)
    {
    }

    public function convert(AbstractQuantityValue $quantityValue, Unit $toUnit): AbstractQuantityValue
    {
        $baseUnit = $toUnit->getBaseunit();

        if (!$baseUnit instanceof \OpenDxp\Model\DataObject\QuantityValue\Unit) {
            $baseUnit = $toUnit;
        }

        $converterServiceName = $baseUnit->getConverter();
        if ($converterServiceName) {
            $converterService = UnitConverterResolver::resolveUnitConverter($converterServiceName);
        } else {
            $converterService = $this->defaultConverter;
        }

        if (!$converterService instanceof QuantityValueConverterInterface) {
            throw new Exception('Converter class needs to implement '.QuantityValueConverterInterface::class);
        }

        return $converterService->convert($quantityValue, $toUnit);
    }
}
