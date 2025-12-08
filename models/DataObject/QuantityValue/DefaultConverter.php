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
use OpenDxp\Model\DataObject\Data\AbstractQuantityValue;
use OpenDxp\Model\DataObject\Data\QuantityValue;
use OpenDxp\Model\Exception\UnsupportedException;

class DefaultConverter implements QuantityValueConverterInterface
{
    /**
     * @throws UnsupportedException If $quantityValue is no QuantityValue
     * @throws Exception
     */
    public function convert(AbstractQuantityValue $quantityValue, Unit $toUnit): AbstractQuantityValue
    {
        if (!$quantityValue instanceof QuantityValue) {
            throw new UnsupportedException('Only QuantityValue is supported.');
        }
        $fromUnit = $quantityValue->getUnit();
        if (!$fromUnit instanceof Unit) {
            throw new Exception('Quantity value has no unit');
        }

        $fromBaseUnit = $fromUnit->getBaseunit();
        if (!$fromBaseUnit instanceof \OpenDxp\Model\DataObject\QuantityValue\Unit) {
            $fromUnit = clone $fromUnit;
            $fromBaseUnit = $fromUnit;
        }

        if ($fromUnit->getFactor() === null) {
            $fromUnit->setFactor(1);
        }

        if ($fromUnit->getConversionOffset() === null) {
            $fromUnit->setConversionOffset(0);
        }

        $toBaseUnit = $toUnit->getBaseunit();
        if (!$toBaseUnit instanceof \OpenDxp\Model\DataObject\QuantityValue\Unit) {
            $toUnit = clone $toUnit;
            $toBaseUnit = $toUnit;
        }

        if ($toUnit->getFactor() === null) {
            $toUnit->setFactor(1);
        }

        if ($toUnit->getConversionOffset() === null) {
            $toUnit->setConversionOffset(0);
        }

        if ($fromBaseUnit->getId() !== $toBaseUnit->getId()) {
            throw new Exception($fromUnit.' must have same base unit as '.$toUnit.' to be able to convert values');
        }

        $convertedValue = ($quantityValue->getValue() * $fromUnit->getFactor() - $fromUnit->getConversionOffset()) / $toUnit->getFactor() + $toUnit->getConversionOffset();

        return new QuantityValue($convertedValue, $toUnit->getId());
    }
}
