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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRunErrorLog;

final readonly class JobRunErrorLogRepository implements JobRunErrorLogRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $openDxpEntityManager,
    ) {
    }

    public function createFromJobRun(
        JobRun $jobRun,
        ?int $elementId = null,
        ?string $message = null
    ): void {
        $jobRunErrorLog = new JobRunErrorLog(
            $jobRun->getId(),
            $jobRun->getCurrentStep(),
            $elementId,
            $message
        );

        $this->openDxpEntityManager->persist($jobRunErrorLog);
        $this->openDxpEntityManager->flush();
    }

    public function update(JobRunErrorLog $jobRunErrorLog): void
    {
        $this->openDxpEntityManager->persist($jobRunErrorLog);
        $this->openDxpEntityManager->flush();
    }

    /**
     * @return JobRunErrorLog[]
     */
    public function getLogsByJobRunId(
        int $jobRunId,
        ?int $step = null,
        array $orderBy = [],
        int $limit = 100,
        int $offset = 0
    ): array {
        $criteria = ['jobRunId' => $jobRunId];
        if ($step !== null && $step >= 0) {
            $criteria['step'] = $step;
        }

        return $this->getLogRepository()->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
    }

    public function getTotalCount(): int
    {
        return $this->getLogRepository()->count([]);
    }

    public function getTotalCountByJobRunId(int $jobRunId): int
    {
        return $this->getLogRepository()->count(['jobRunId' => $jobRunId]);
    }

    private function getLogRepository(): EntityRepository
    {
        return $this->openDxpEntityManager->getRepository(JobRunErrorLog::class);
    }
}
