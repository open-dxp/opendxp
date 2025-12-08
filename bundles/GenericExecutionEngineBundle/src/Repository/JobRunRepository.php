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

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\CurrentMessage\CurrentMessageProviderInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\Job;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Security\PermissionServiceInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Translation\Translator;
use Psr\Log\LoggerInterface;

final readonly class JobRunRepository implements JobRunRepositoryInterface
{
    public function __construct(
        private Connection $db,
        private CurrentMessageProviderInterface $currentMessageProvider,
        private EntityManagerInterface $openDxpEntityManager,
        private ExecutionContextInterface $executionContext,
        private LoggerInterface $genericExecutionEngineLogger,
        private PermissionServiceInterface $permissionService,
        private Translator $translator,
    ) {
    }

    public function createFromJob(Job $job, ?int $ownerId = null): JobRun
    {
        $jobRun = new JobRun($ownerId);

        $jobRun->setJob($job);

        $this->openDxpEntityManager->persist($jobRun);
        $this->openDxpEntityManager->flush();

        return $jobRun;
    }

    public function update(JobRun $jobRun): JobRun
    {

        $this->openDxpEntityManager->persist($jobRun);
        $this->openDxpEntityManager->flush();

        return $jobRun;
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public function updateLogLocalizedWithDomain(
        JobRun $jobRun,
        string $message,
        array $params = [],
        bool $updateCurrentMessage = true,
        string $defaultLocale = 'en',
        string $domain = 'admin'
    ): void {
        if ($updateCurrentMessage) {
            $jobRun->setCurrentMessageLocalized(
                $this->currentMessageProvider->getTranslationMessages($message, $params, $domain)
            );
            $this->update($jobRun);
        }

        $translatedMessage = $this->translator->trans($message, $params, $domain, $defaultLocale);
        $this->updateLog($jobRun, $translatedMessage);
    }

    public function updateLogLocalized(
        JobRun $jobRun,
        string $message,
        array $params = [],
        bool $updateCurrentMessage = true,
        string $defaultLocale = 'en'
    ): void {
        $domain = $this->executionContext->getTranslationDomain($jobRun->getExecutionContext());

        $this->updateLogLocalizedWithDomain(
            $jobRun,
            $message,
            $params,
            $updateCurrentMessage,
            $defaultLocale,
            $domain
        );
    }

    /**
     * @throws Exception
     */
    public function updateLog(JobRun $jobRun, string $message): void
    {

        $this->db->executeStatement(
            'UPDATE ' .
            TableConstants::JOB_RUN_TABLE .
            ' SET log = IF(ISNULL(log),:message,CONCAT(log, "\n", :message)) WHERE id = :id',
            [
                'id' => $jobRun->getId(),
                'message' => (new DateTimeImmutable())->format('c') . ': ' . trim($message),
            ]
        );

        $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: " . $message);

        $this->openDxpEntityManager->refresh($jobRun);
    }

    public function getJobRunById(int $id, bool $forceReload = false, ?int $ownerId = null): JobRun
    {

        $params = ['id' => $id];
        if ($ownerId !== null && !$this->permissionService->isAllowedToSeeAllJobRuns()) {
            $params['ownerId'] = $ownerId;
        }

        $jobRun = $this->openDxpEntityManager->getRepository(JobRun::class)->findOneBy($params);
        if (!$jobRun) {
            throw new NotFoundException("JobRun with id $id not found.");
        }

        if ($forceReload) {
            $this->openDxpEntityManager->refresh($jobRun);
        }

        return $jobRun;

    }

    /**
     * Get all job runs by user id. If user has permission to see all job runs, all job runs will be returned.
     *
     * @return JobRun[]
     *
     */
    public function getJobRunsByUserId(
        ?int $ownerId = null,
        array $orderBy = [],
        int $limit = 100,
        int $offset = 0
    ): array {
        $params = [];
        if ($ownerId !== null && !$this->permissionService->isAllowedToSeeAllJobRuns()) {
            $params['ownerId'] = $ownerId;
        }

        return $this->openDxpEntityManager->getRepository(JobRun::class)->findBy(
            $params,
            $orderBy,
            $limit,
            $offset
        );
    }

    public function getTotalCount(): int
    {
        return $this->openDxpEntityManager->getRepository(JobRun::class)->count([]);
    }

    public function getRunningJobsByUserId(
        int $ownerId,
        array $orderBy = [],
        int $limit = 10,
    ): array {
        return $this->openDxpEntityManager
            ->getRepository(JobRun::class)
            ->findBy(
                ['ownerId' => $ownerId, 'state' => JobRunStates::RUNNING],
                $orderBy,
                $limit
            );
    }

    public function getLastJobRunByName(string $name): ?JobRun
    {
        $result = $this->openDxpEntityManager->getRepository(JobRun::class)
            ->createQueryBuilder('JobRun')
            ->where('JobRun.serializedJob LIKE :name')
            ->setParameter('name', '%name":"' . $name . '"%')
            ->orderBy('JobRun.modificationDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    /**
     * @throws Exception
     */
    public function updateSelectedElements(JobRun $jobRun, array $selectedElements): void
    {
        $job = $jobRun->getJob();
        if (!$job) {
            throw new JobNotFoundException('Job not found for JobRun with id: ' . $jobRun->getId());
        }
        $currentlySelectedElements = $job->getSelectedElements();
        $job->setSelectedElements($selectedElements);
        $this->update($jobRun);
        $this->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_updated_selected_elements',
            [
                '%fromCount%' => count($currentlySelectedElements),
                '%toCount%' => count($selectedElements),
            ]
        );
    }
}
