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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Extractor;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use OpenDxp\Model\Element\ElementInterface;

/**
 * Adds the method checkStepCondition() in 2.0.
 *
 * @method bool checkStepCondition(JobRun $jobRun, JobStepInterface $jobStep)
 */
interface JobRunExtractorInterface
{
    public const string ASSET_TYPE = 'asset';

    public const string DOCUMENT_TYPE = 'document';

    public const string FOLDER_TYPE = 'folder';

    public const string OBJECT_TYPE = 'object';

    public function getJobRun(GenericExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun;

    public function getJobStep(GenericExecutionEngineMessageInterface $message): JobStepInterface;

    public function getEnvironmentData(JobRun $jobRun): array;

    public function checkCondition(GenericExecutionEngineMessageInterface $message): bool;

    public function logMessageToJobRun(
        JobRun $jobRun,
        string $translationKey,
        array $params = []
    ): void;

    public function getElementFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): ?ElementInterface;

    /** @return ElementInterface[]  */
    public function getElementsFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): array;
}
