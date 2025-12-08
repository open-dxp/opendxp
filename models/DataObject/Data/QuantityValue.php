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

use Exception;
use NumberFormatter;
use OpenDxp;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\QuantityValue\Unit;

class QuantityValue extends AbstractQuantityValue
{
    public function __construct(protected float|int|string|null $value = null, Unit|string|null $unit = null)
    {
        parent::__construct($unit);
    }

    public function setValue(float|int|string|null $value): void
    {
        $this->value = $value;
        $this->markMeDirty();
    }

    public function getValue(): float|int|string|null
    {
        return $this->value;
    }

    /**
     * @throws Exception
     */
    public function __toString(): string
    {
        $value = $this->getValue();
        if (is_numeric($value)) {
            $locale = OpenDxp::getContainer()->get(LocaleServiceInterface::class)->findLocale();

            if ($locale) {
                $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
                $value = $formatter->format((float) $value);
            }
        }

        $unit = $this->getUnit();
        if ($unit instanceof Unit) {
            if ($unit->getAbbreviation() === null) {
                $unitAbbreviation = $unit->getId();
            } else {
                $translator = OpenDxp::getContainer()->get('translator');
                $unitAbbreviation = $translator->trans($unit->getAbbreviation(), [], 'admin');
            }

            $value .= ' ' . $unitAbbreviation;
        }

        return $value ? (string)$value : '';
    }
}
