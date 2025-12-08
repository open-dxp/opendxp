<?php

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

namespace OpenDxp\Bundle\SeoBundle\Model\Redirect\Listing;

use Exception;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Model\Redirect\Listing;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Redirect elements
     *
     */
    public function load(): array
    {
        $redirectsData = $this->db->fetchFirstColumn('SELECT id FROM redirects' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $redirects = [];
        foreach ($redirectsData as $redirectData) {
            $redirects[] = Redirect::getById($redirectData);
        }

        $this->model->setRedirects(array_filter($redirects));

        return $redirects;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM redirects ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
