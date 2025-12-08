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

namespace OpenDxp\Model\DataObject\QuantityValue\Unit;

use OpenDxp\Model;
use Override;

/**
 * @method \OpenDxp\Model\DataObject\QuantityValue\Unit\Listing\Dao getDao()
 * @method Model\DataObject\QuantityValue\Unit[] load()
 * @method Model\DataObject\QuantityValue\Unit|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    #[Override]
    public function isValidOrderKey(string $key): bool
    {
        return in_array($key, ['abbreviation', 'group', 'id', 'longname', 'baseunit', 'factor'], true);
    }

    /**
     * @return Model\DataObject\QuantityValue\Unit[]
     */
    public function getUnits(): array
    {
        return $this->getData();
    }

    /**
     * @param Model\DataObject\QuantityValue\Unit[]|null $units
     *
     * @return $this
     */
    public function setUnits(?array $units): static
    {
        return $this->setData($units);
    }
}
