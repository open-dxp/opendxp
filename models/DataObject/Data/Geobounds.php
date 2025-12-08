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

use OpenDxp\Model\DataObject\OwnerAwareFieldInterface;
use OpenDxp\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Stringable;

class Geobounds implements OwnerAwareFieldInterface, Stringable
{
    use OwnerAwareFieldTrait;

    protected ?GeoCoordinates $northEast = null;

    protected ?GeoCoordinates $southWest = null;

    public function __construct(?GeoCoordinates $northEast = null, ?GeoCoordinates $southWest = null)
    {
        if ($northEast) {
            $this->setNorthEast($northEast);
        }
        if ($southWest) {
            $this->setSouthWest($southWest);
        }
        $this->markMeDirty();
    }

    public function getNorthEast(): ?GeoCoordinates
    {
        return $this->northEast;
    }

    /**
     * @return $this
     */
    public function setNorthEast(?GeoCoordinates $northEast): static
    {
        $this->northEast = $northEast;
        $this->markMeDirty();

        return $this;
    }

    public function getSouthWest(): ?GeoCoordinates
    {
        return $this->southWest;
    }

    /**
     * @return $this
     */
    public function setSouthWest(?GeoCoordinates $southWest): static
    {
        $this->southWest = $southWest;
        $this->markMeDirty();

        return $this;
    }

    public function __toString(): string
    {
        $string = '';
        if ($this->northEast) {
            $string .= $this->northEast;
        }
        if (!empty($string)) {
            $string .= ' - ';
        }
        if ($this->southWest) {
            $string .= $this->southWest;
        }

        return $string;
    }
}
