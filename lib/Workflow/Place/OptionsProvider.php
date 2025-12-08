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

namespace OpenDxp\Workflow\Place;

use Exception;
use OpenDxp;
use OpenDxp\Helper\ContrastColor;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Multiselect;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Select;
use OpenDxp\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use OpenDxp\Workflow\Manager;
use OpenDxp\Workflow\MarkingStore\DataObjectSplittedStateMarkingStore;
use Symfony\Contracts\Translation\TranslatorInterface;

class OptionsProvider implements SelectOptionsProviderInterface
{
    public function __construct(private readonly Manager $workflowManager, private readonly TranslatorInterface $translator)
    {
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $workflowName = null;
        if ($fieldDefinition instanceof Select || $fieldDefinition instanceof Multiselect) {
            $workflowName = $fieldDefinition->getOptionsProviderData();
        }
        if (!$workflowName) {
            throw new Exception('setup workflow name as options provider data');
        }

        $options = [];

        $workflow = $this->workflowManager->getWorkflowByName($workflowName);

        $mappedPlaces = null;
        $markingStore = $workflow->getMarkingStore();

        if ($markingStore instanceof DataObjectSplittedStateMarkingStore) {
            $mappedPlaces = $markingStore->getMappedPlaces($fieldDefinition->getName());
        }

        foreach ($this->workflowManager->getPlaceConfigsByWorkflowName($workflowName) as $placeConfig) {
            if (!is_array($mappedPlaces) || in_array($placeConfig->getPlace(), $mappedPlaces)) {
                $options[] = [
                    'key' => $this->generatePlaceLabel($placeConfig),
                    'value' => $placeConfig->getPlace(),
                ];
            }
        }

        return $options;
    }

    protected function generatePlaceLabel(PlaceConfig $placeConfig): string
    {
        // do not translate or format options when not in admin context
        if (empty($this->translator->getLocale()) || !OpenDxp::inAdmin()) {
            return $placeConfig->getLabel();
        }

        // disabled for the moment
        return sprintf('<div class="opendxp-workflow-place-indicator" style="background-color: %s; color:%s">%s</div>',
            $placeConfig->getColor(),
            ContrastColor::getContrastColor($placeConfig->getColor()),
            $this->translator->trans($placeConfig->getLabel(), [], 'admin')
        );
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): ?string
    {
        if ($fieldDefinition instanceof Data\Select) {
            return $fieldDefinition->getDefaultValue();
        }

        return null;
    }
}
