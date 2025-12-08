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

namespace OpenDxp\Bundle\GlossaryBundle\Model\Glossary\Listing;

use Exception;
use OpenDxp\Bundle\GlossaryBundle\Model\Glossary;
use OpenDxp\Bundle\GlossaryBundle\Model\Glossary\Listing;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Staticroute elements
     *
     * @return Glossary[]
     */
    public function load(): array
    {
        $glossarysData = $this->db->fetchFirstColumn('SELECT id FROM glossary' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $glossary = [];
        foreach ($glossarysData as $glossaryData) {
            $glossary[] = Glossary::getById($glossaryData);
        }

        $this->model->setGlossary($glossary);

        return $glossary;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM glossary' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    /**
     *
     * @todo: $amount could not be defined, so this could cause an issue
     */
    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM glossary ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception) {
            return 0;
        }
    }
}
