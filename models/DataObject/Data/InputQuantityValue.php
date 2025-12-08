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
use OpenDxp;
use OpenDxp\Model\DataObject\QuantityValue\Unit;

class InputQuantityValue extends AbstractQuantityValue
{
    public function __construct(protected string|null $value = null, Unit|string|null $unit = null)
    {
        parent::__construct($unit);
    }

    public function setValue(string|null $value): void
    {
        $this->value = $value;
        $this->markMeDirty();
    }

    public function getValue(): string|null
    {
        return $this->value;
    }

    /**
     * @throws Exception
     */
    public function __toString(): string
    {
        $value = $this->getValue();
        if ($this->getUnit() instanceof Unit) {
            $translator = OpenDxp::getContainer()->get('translator');
            $value .= ' ' . $translator->trans($this->getUnit()->getAbbreviation(), [], 'admin');
        }

        return $value ?? '';
    }
}
