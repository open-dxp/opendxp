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

namespace OpenDxp\Model\DataObject\Data;

use NumberFormatter;
use OpenDxp;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use OpenDxp\Model\DataObject\Traits\ObjectVarTrait;

class QuantityValueRange extends AbstractQuantityValue
{
    use ObjectVarTrait;

    public function __construct(protected int|float|null $minimum, protected int|float|null $maximum, Unit|string|null $unit)
    {
        parent::__construct($unit);

        $this->markMeDirty();
    }

    public function getMinimum(): int|float|null
    {
        return $this->minimum;
    }

    public function setMinimum(int|float|null $minimum): void
    {
        $this->minimum = $minimum;

        $this->markMeDirty();
    }

    public function getMaximum(): int|float|null
    {
        return $this->maximum;
    }

    public function setMaximum(int|float|null $maximum): void
    {
        $this->maximum = $maximum;

        $this->markMeDirty();
    }

    public function getRange(int $step = 1): array
    {
        $min = $this->getMinimum();
        $max = $this->getMaximum();
        if (is_null($min) || is_null($max)) {
            return [0];
        }

        return range($min, $max, $step);
    }

    public function getValue(int $step = 1): array
    {
        return $this->getRange($step);
    }

    public function toArray(): array
    {
        return [
            'minimum' => $this->getMinimum(),
            'maximum' => $this->getMaximum(),
            'unitId' => $this->getUnitId(),
        ];
    }

    public function __toString(): string
    {
        $locale = OpenDxp::getContainer()->get(LocaleServiceInterface::class)->findLocale();

        $minimum = $this->getMinimum() ?: '-∞';
        $maximum = $this->getMaximum() ?: '+∞';
        $unit = $this->getUnit();

        if (is_numeric($minimum) && $locale) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $minimum = $formatter->format($minimum);
        }

        if (is_numeric($maximum) && $locale) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $maximum = $formatter->format($maximum);
        }

        if ($unit instanceof Unit) {
            $translator = OpenDxp::getContainer()->get('translator');
            $unit = $translator->trans($unit->getAbbreviation(), [], 'admin');
        }

        return sprintf('[%s, %s] %s', $minimum, $maximum, $unit);
    }
}
