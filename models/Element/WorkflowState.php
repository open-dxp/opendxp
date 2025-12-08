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

namespace OpenDxp\Model\Element;

use OpenDxp\Model;

/**
 * @method void delete()
 * @method \OpenDxp\Model\Element\WorkflowState\Dao getDao()
 * @method void save()
 */
class WorkflowState extends Model\AbstractModel
{
    protected int $cid;

    protected string $ctype;

    protected string $workflow;

    protected string $place;

    public static function getByPrimary(int $cid, string $ctype, string $workflow): ?WorkflowState
    {
        try {
            $workflowState = new self();
            $workflowState->getDao()->getByPrimary($cid, $ctype, $workflow);

            return $workflowState;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    /**
     * @return $this
     */
    public function setCid(int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    /**
     * @return $this
     */
    public function setPlace(string $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getWorkflow(): string
    {
        return $this->workflow;
    }

    /**
     * @return $this
     */
    public function setWorkflow(string $workflow): static
    {
        $this->workflow = $workflow;

        return $this;
    }
}
