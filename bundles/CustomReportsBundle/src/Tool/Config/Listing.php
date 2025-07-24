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

namespace OpenDxp\Bundle\CustomReportsBundle\Tool\Config;

use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\Listing\CallableFilterListingInterface;
use OpenDxp\Model\Listing\CallableOrderListingInterface;
use OpenDxp\Model\Listing\Traits\FilterListingTrait;
use OpenDxp\Model\Listing\Traits\OrderListingTrait;

/**
 * @internal
 *
 * @method \OpenDxp\Bundle\CustomReportsBundle\Tool\Config\Listing\Dao getDao()
 */
class Listing extends AbstractModel implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    /**
     * @var \OpenDxp\Bundle\CustomReportsBundle\Tool\Config[]|null
     */
    protected ?array $reports = null;

    /**
     * @return \OpenDxp\Bundle\CustomReportsBundle\Tool\Config[]
     */
    public function getReports(): array
    {
        if ($this->reports === null) {
            $this->reports = $this->getDao()->loadList();
        }

        return $this->reports;
    }

    /**
     * @param\OpenDxp\Bundle\CustomReportsBundle\Tool\Config[]|null $reports
     *
     * @return $this
     */
    public function setReports(?array $reports): static
    {
        $this->reports = $reports;

        return $this;
    }
}
