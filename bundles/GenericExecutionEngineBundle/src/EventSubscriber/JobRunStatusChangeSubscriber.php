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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\EventSubscriber;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class JobRunStatusChangeSubscriber
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private JobRunRepositoryInterface $jobRunRepository
    ) {

    }

    private const string STATE_FIELD = 'state';

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof JobRun) {
            return;
        }

        if ($args->hasChangedField(self::STATE_FIELD)) {
            $oldStatus = $args->getOldValue(self::STATE_FIELD);
            $newStatus = $args->getNewValue(self::STATE_FIELD);

            if ($oldStatus !== $newStatus) {
                $jobRun = $this->jobRunRepository->getJobRunById($entity->getId());
                $jobName = $jobRun->getJob()?->getName();
                $event = new JobRunStateChangedEvent(
                    jobRunId: $entity->getId(),
                    jobName: $jobName,
                    jobRunOwnerId: $jobRun->getOwnerId(),
                    oldState: $oldStatus,
                    newState: $newStatus
                );
                $this->eventDispatcher->dispatch($event);
            }
        }
    }
}
