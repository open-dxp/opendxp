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

class GeoCoordinates implements OwnerAwareFieldInterface, \Stringable
{
    use OwnerAwareFieldTrait;

    protected ?float $longitude = null;

    protected ?float $latitude = null;

    public function __construct(?float $latitude = null, ?float $longitude = null)
    {
        if ($latitude !== null) {
            $this->setLatitude($latitude);
        }

        if ($longitude !== null) {
            $this->setLongitude($longitude);
        }

        $this->markMeDirty();
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * @return $this
     */
    public function setLongitude(?float $longitude): static
    {
        if ($this->longitude !== $longitude) {
            $this->longitude = $longitude;
            $this->markMeDirty();
        }

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @return $this
     */
    public function setLatitude(?float $latitude): static
    {
        if ($this->latitude !== $latitude) {
            $this->latitude = $latitude;
            $this->markMeDirty();
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->latitude . '; ' . $this->longitude;
    }
}
