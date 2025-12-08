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

namespace OpenDxp\Bundle\CoreBundle\EventListener;

use Exception;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\ElementEventInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Concrete as ConcreteObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Element\WorkflowState;
use OpenDxp\Workflow\Manager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @internal
 */
class WorkflowManagementListener implements EventSubscriberInterface
{
    protected bool $enabled = true;

    public function __construct(
        private readonly Manager $workflowManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_ADD => 'onElementPostAdd',
            DocumentEvents::POST_ADD => 'onElementPostAdd',
            AssetEvents::POST_ADD => 'onElementPostAdd',

            DataObjectEvents::POST_DELETE => 'onElementPostDelete',
            DocumentEvents::POST_DELETE => 'onElementPostDelete',
            AssetEvents::POST_DELETE => 'onElementPostDelete',
        ];
    }

    /**
     * Set initial place if defined on element create.
     */
    public function onElementPostAdd(ElementEventInterface $e): void
    {
        /** @var Asset|Document|ConcreteObject $element */
        $element = $e->getElement();

        foreach ($this->workflowManager->getAllWorkflows() as $workflowName) {
            $workflow = $this->workflowManager->getWorkflowIfExists($element, $workflowName);
            if (!$workflow) {
                continue;
            }

            $hasInitialPlaceConfig = count($this->workflowManager->getInitialPlacesForWorkflow($workflow)) > 0;

            // calling getMarking will ensure the initial place is set
            if ($hasInitialPlaceConfig) {
                $workflow->getMarking($element);
            }
        }
    }

    /**
     * Cleanup status information on element delete
     *
     */
    public function onElementPostDelete(ElementEventInterface $e): void
    {
        /**
         * @var Asset|Document|ConcreteObject $element
         */
        $element = $e->getElement();

        $list = new WorkflowState\Listing;
        $list->setCondition('cid = ? and ctype = ?', [$element->getId(), Service::getElementType($element)]);

        foreach ($list->load() as $item) {
            $item->delete();
        }
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
