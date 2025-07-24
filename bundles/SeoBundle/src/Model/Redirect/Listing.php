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

namespace OpenDxp\Bundle\SeoBundle\Model\Redirect;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Model;

/**
 * @method\OpenDxp\Bundle\SeoBundle\Model\Redirect\Listing\Dao getDao()
 *
 * @method Redirect[] load()
 * @method Redirect|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Redirect[]
     */
    public function getRedirects(): array
    {
        return $this->getData();
    }

    /**
     * @param Redirect[]|null $redirects
     *
     * @return $this
     */
    public function setRedirects(?array $redirects): static
    {
        return $this->setData($redirects);
    }
}
