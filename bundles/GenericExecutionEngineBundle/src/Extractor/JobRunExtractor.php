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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Extractor;

use Doctrine\DBAL\Exception;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use OpenDxp\Helper\SymfonyExpression\ExpressionServiceInterface;
use OpenDxp\Model\Element\ElementDescriptor;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Exception\NotFoundException;

final readonly class JobRunExtractor implements JobRunExtractorInterface
{
    public function __construct(
        private ExpressionServiceInterface $symfonyExpressionService,
        private JobRunRepositoryInterface $jobRunRepository
    ) {
    }

    public function getJobRun(GenericExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun
    {
        return $this->jobRunRepository->getJobRunById($message->getJobRunId(), $forceReload);
    }

    public function getJobStep(GenericExecutionEngineMessageInterface $message): JobStepInterface
    {
        $jobRun = $this->getJobRun($message);
        $jobSteps = $jobRun->getJob()?->getSteps();

        if (!array_key_exists($message->getCurrentJobStep(), $jobSteps)) {
            throw new NotFoundException('Job not found!');
        }

        return $jobSteps[$message->getCurrentJobStep()];
    }

    public function getEnvironmentData(JobRun $jobRun): array
    {
        if (!$jobRun->getJob()) {
            return [];
        }

        return $jobRun->getJob()->getEnvironmentData();
    }

    /**
     * Logs a translation key to the job run which can be viewed in the job run overview
     *
     * @throws Exception
     */
    public function logMessageToJobRun(
        JobRun $jobRun,
        string $translationKey,
        array $params = []
    ): void {
        $this->jobRunRepository->updateLogLocalized(
            $jobRun,
            $translationKey,
            $params
        );
    }

    public function checkCondition(GenericExecutionEngineMessageInterface $message): bool
    {
        $jobRun = $this->getJobRun($message);
        $jobRunContext = $jobRun->getContext();
        $currentStep = $this->getJobStep($message);
        $currentStepCondition = $currentStep->getCondition();
        $contentVariables = $this->extractContentVariables($jobRunContext, $this->getEnvironmentData($jobRun));

        if ($currentStepCondition === '') {
            return true;
        }

        return $this->symfonyExpressionService->evaluate($currentStepCondition, $contentVariables);
    }

    private function extractContentVariables(
        ?array $jobRunContext = null,
        ?array $environmentData = null
    ): array {
        $variables = [];
        if (!empty($jobRunContext)) {
            $variables['context'] = $jobRunContext;
        }
        if (!empty($environmentData)) {
            $variables['environmentData'] = $environmentData;
        }

        return $variables;
    }

    public function getElementFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): ?ElementInterface {
        $elementDescriptor = $message->getElement();
        if (!$elementDescriptor) {
            return null;
        }

        $element = $this->getElementByType(
            $elementDescriptor->getType(),
            $elementDescriptor->getId(),
            $types
        );

        if (!$element) {
            return null;
        }

        return $element;
    }

    public function getElementsFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): array {

        $elementsToProcess = [];
        $jobRun = $this->getJobRun($message);

        /** @var ElementDescriptor[] $elementDescriptors */
        $elementDescriptors = $jobRun->getJob()?->getSelectedElements();

        foreach ($elementDescriptors as $elementDescriptor) {
            $element = $this->getElementByType(
                $elementDescriptor->getType(),
                $elementDescriptor->getId(),
                $types
            );
            if ($element instanceof \OpenDxp\Model\Element\ElementInterface) {
                $elementsToProcess[] = $element;
            }
        }

        return $elementsToProcess;
    }

    private function getElement(string $type, int $id): ?ElementInterface
    {
        $element = Service::getElementById($type, $id);

        if (!$element || $element->getType() === JobRunExtractorInterface::FOLDER_TYPE) {
            return null;
        }

        return $element;
    }

    private function getElementByType(
        string $elementType,
        int $elementId,
        array $typesToLookFor = [JobRunExtractorInterface::ASSET_TYPE]): ?ElementInterface
    {

        if (!in_array($elementType, $typesToLookFor, true)) {
            return null;
        }

        return $this->getElement($elementType, $elementId);
    }
}
