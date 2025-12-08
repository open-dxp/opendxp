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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Grid;

use DateTimeInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\CurrentMessage\CurrentMessageProviderInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects\LogLine;
use OpenDxp\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final readonly class JobRunGrid implements JobRunGridInterface
{
    public function __construct(
        private CurrentMessageProviderInterface $currentMessageProvider,
        private ExecutionContextInterface $executionContext,
        private TranslatorInterface $translator
    ) {
    }

    public function convertJobRunToArray(JobRun $jobRun): array
    {
        $currentMessage = $this->currentMessageProvider->getMessageFromSerializedString(
            $jobRun->getCurrentMessage()
        );

        $domain = $this->executionContext->getTranslationDomain($jobRun->getExecutionContext());

        return [
            'id' => $jobRun->getId(),
            'job_name' => $this->translator->trans($jobRun->getJob()?->getName(), [], $domain),
            'started_at' => $jobRun->getCreationDate(),
            'last_update_at' => $jobRun->getModificationDate(),
            'owner' => $jobRun->getOwnerId() ? User::getById($jobRun->getOwnerId())?->getName() : null,
            'state' => $jobRun->getState()->value,
            'current_step' => $jobRun->getCurrentStep(),
            'current_message' => $currentMessage->getMessage(),
            'canCancel' => $jobRun->getState() === JobRunStates::RUNNING,
            'log' => array_map(
                static fn (LogLine $line) =>
                [
                    'logMessage' => $line->getLogLine(),
                    'createdAt' => $line->getCreatedAt()->format(DateTimeInterface::ATOM),
                ],
                $jobRun->getLogs()
            ),
        ];
    }
}
