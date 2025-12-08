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

use OpenDxp\Workflow\Manager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class StatusInfo
{
    private readonly string $userLanguage;

    public function __construct(private readonly Manager $workflowManager, private readonly Environment $twig, private readonly TranslatorInterface $translator)
    {
        $user = \OpenDxp\Tool\Admin::getCurrentUser();
        $this->userLanguage = $user ? $user->getLanguage() : 'en';
    }

    public function getToolbarHtml(object $subject): string
    {
        $places = $this->getAllPlaces($subject, true);

        return $this->twig->render(
            '@OpenDxpCore/Workflow/statusinfo/toolbarStatusInfo.html.twig',
            [
                'places' => $places,
                'translator' => $this->translator,
                'lang' => $this->userLanguage,
            ]
        );
    }

    public function getAllPalacesHtml(object $subject, ?string $workflowName = null): string
    {
        $places = $this->getAllPlaces($subject, false, $workflowName);

        return $this->twig->render(
            '@OpenDxpCore/Workflow/statusinfo/allPlacesStatusInfo.html.twig',
            [
                'places' => $places,
                'translator' => $this->translator,
                'lang' => $this->userLanguage,
            ]
        );
    }

    public function getAllPlacesForCsv(object $subject, ?string $workflowName = null): string
    {
        $places = $this->getAllPlaces($subject, false, $workflowName);
        $result = [];

        foreach ($places as $place) {
            $result[] = $place->getLabel();
        }

        return implode(', ', $result);
    }

    /**
     * @return PlaceConfig[]
     */
    private function getAllPlaces(object $subject, bool $visibleInHeaderOnly = false, ?string $workflowName = null): array
    {
        $places = [];

        foreach ($this->workflowManager->getAllWorkflowsForSubject($subject) as $workflow) {
            if (!is_null($workflowName) && $workflow->getName() != $workflowName) {
                continue;
            }

            $marking = $workflow->getMarking($subject);
            foreach ($this->workflowManager->getOrderedPlaceConfigs($workflow, $marking) as $place) {
                if (!$visibleInHeaderOnly || $place->isVisibleInHeader()) {
                    $places[] = $place;
                }
            }
        }

        return $this->filterPlaces($places);
    }

    /**
     * Multiple parallel workflows with the same places should not result in multiple status labels
     *
     * @param PlaceConfig[] $places
     *
     * @return PlaceConfig[]
     */
    protected function filterPlaces(array $places): array
    {
        $uniquePlaces = [];
        foreach ($places as $place) {
            $uniquePlaces[$place->getPlace()] = $place;
        }

        return array_values($uniquePlaces);
    }
}
