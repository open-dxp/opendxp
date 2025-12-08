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

namespace OpenDxp\Workflow\MarkingStore;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Element\WorkflowState;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class StateTableMarkingStore implements MarkingStoreInterface
{
    public function __construct(private readonly string $workflowName)
    {
    }

    public function getMarking(object $subject): Marking
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        $placeName = '';

        if ($workflowState = WorkflowState::getByPrimary($subject->getId(), Service::getElementType($subject), $this->workflowName)) {
            $placeName = $workflowState->getPlace();
        }

        if (!$placeName) {
            return new Marking();
        }

        $placeName = explode(',', $placeName);
        $places = [];
        foreach ($placeName as $place) {
            $places[$place] = 1;
        }

        return new Marking($places);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $subject = $this->checkIfSubjectIsValid($subject);
        $type = Service::getElementType($subject);

        if (!$workflowState = WorkflowState::getByPrimary($subject->getId(), $type, $this->workflowName)) {
            $workflowState = new WorkflowState();
            $workflowState->setCtype($type);
            $workflowState->setCid($subject->getId());
            $workflowState->setWorkflow($this->workflowName);
        }

        $workflowState->setPlace(implode(',', array_keys($marking->getPlaces())));
        $workflowState->save();
    }

    public function getProperty(): string
    {
        return $this->workflowName;
    }

    /**
     * @throws LogicException
     */
    private function checkIfSubjectIsValid(object $subject): ElementInterface
    {
        if (!$subject instanceof ElementInterface) {
            throw new LogicException('state_table marking store works for opendxp elements (documents, assets, data objects) only.');
        }

        return $subject;
    }
}
