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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\CurrentMessage\CurrentMessageProviderInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\Job;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunFilter;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunResult;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Security\PermissionServiceInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use OpenDxp\DateFormat;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\Clock;

final readonly class JobRunRepository implements JobRunRepositoryInterface
{
    public function __construct(
        private Connection $db,
        private CurrentMessageProviderInterface $currentMessageProvider,
        private EntityManagerInterface $openDxpEntityManager,
        private ExecutionContextInterface $executionContext,
        private JobRunArtifactStorage $jobRunArtifactStorage,
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
            sprintf(
                'UPDATE %s SET log = IF(ISNULL(log),:message,CONCAT(log, "\n", :message)) WHERE id = :id',
                TableConstants::JOB_RUN_TABLE
            ),
            [
                'id' => $jobRun->getId(),
                'message' => (new DateTimeImmutable())->format(DateFormat::ISO_8601) . ': ' . trim($message),
            ]
        );

        $this->genericExecutionEngineLogger->info(sprintf('[JobRun %d]: %s', $jobRun->getId(), $message));

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
            throw new NotFoundException(sprintf('JobRun with id %d not found.', $id));
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

    /**
     * @internal
     */
    public function findState(int $jobRunId): ?JobRunStates
    {
        $state = $this->db->fetchOne(
            sprintf('SELECT state FROM %s WHERE id = ?', TableConstants::JOB_RUN_TABLE),
            [$jobRunId],
        );

        return $state === false ? null : JobRunStates::from($state);
    }

    /**
     * Adds up the progress, so parallel workers do not overwrite each other.
     *
     * @param array{message: string, parameters: array<string, scalar|null>}|null $currentMessage
     *
     * @internal
     */
    public function updateReport(int $jobRunId, ?int $total, int $steps, ?array $currentMessage): void
    {
        $assignments = ['progress = progress + :steps', 'modificationDate = :now'];
        $parameters = ['id' => $jobRunId, 'steps' => $steps, 'now' => Clock::get()->now()->getTimestamp()];

        if ($total !== null) {
            $assignments[] = 'total = :total';
            $parameters['total'] = $total;
        }

        if ($currentMessage !== null) {
            $executionContext = (string) $this->db->fetchOne(
                sprintf('SELECT executionContext FROM %s WHERE id = ?', TableConstants::JOB_RUN_TABLE),
                [$jobRunId],
            );
            $assignments[] = 'currentMessage = :currentMessage';
            $parameters['currentMessage'] = $this->currentMessageProvider->getTranslationMessages(
                $currentMessage['message'],
                $currentMessage['parameters'],
                $this->executionContext->getTranslationDomain($executionContext),
            )->getSerializedString();
        }

        $this->db->executeStatement(
            sprintf('UPDATE %s SET %s WHERE id = :id', TableConstants::JOB_RUN_TABLE, implode(', ', $assignments)),
            $parameters,
        );
    }

    /**
     * @internal
     */
    public function updateResult(int $jobRunId, JobRunResult $result): void
    {
        $this->db->update(
            TableConstants::JOB_RUN_TABLE,
            ['result' => json_encode($result->toArray(), JSON_THROW_ON_ERROR)],
            ['id' => $jobRunId, 'state' => JobRunStates::RUNNING->value],
        );
    }

    /**
     * @internal
     */
    public function delete(JobRun $jobRun): void
    {
        $this->jobRunArtifactStorage->delete($jobRun->getId());
        $this->openDxpEntityManager->remove($jobRun);
        $this->openDxpEntityManager->flush();
    }

    /**
     * @param list<string>|null $executionContexts
     *
     * @return list<int>
     *
     * @internal
     */
    public function getIdsEndedBefore(int $timestamp, ?array $executionContexts = null): array
    {
        $query = $this->db->createQueryBuilder()
            ->select('id')
            ->from(TableConstants::JOB_RUN_TABLE)
            ->where('state != :running')
            ->andWhere('modificationDate < :timestamp')
            ->setParameter('running', JobRunStates::RUNNING->value)
            ->setParameter('timestamp', $timestamp);

        if ($executionContexts !== null) {
            $query->andWhere('executionContext IN (:executionContexts)')
                ->setParameter('executionContexts', $executionContexts, ArrayParameterType::STRING);
        }

        return array_map(intval(...), $query->fetchFirstColumn());
    }

    /**
     * @return list<int>
     *
     * @internal
     */
    public function getRunningIdsInactiveSince(int $timestamp): array
    {
        return array_map(intval(...), $this->db->fetchFirstColumn(
            sprintf('SELECT id FROM %s WHERE state = ? AND modificationDate < ?', TableConstants::JOB_RUN_TABLE),
            [JobRunStates::RUNNING->value, $timestamp],
        ));
    }

    /**
     * @return list<array{jobName: string, executionContext: string}>
     *
     * @internal
     */
    public function getJobNames(?int $ownerId): array
    {
        $query = $this->openDxpEntityManager->getRepository(JobRun::class)
            ->createQueryBuilder('jobRun')
            ->select('jobRun.jobName, jobRun.executionContext')
            ->distinct()
            ->where('jobRun.jobName IS NOT NULL')
            ->orderBy('jobRun.jobName');

        if ($ownerId !== null) {
            $query->andWhere('jobRun.ownerId = :ownerId')->setParameter('ownerId', $ownerId);
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param list<string> $disposableContexts
     *
     * @return list<JobRun>
     *
     * @internal
     */
    public function getJobRunsForMenu(int $ownerId, array $disposableContexts, int $limit): array
    {
        $query = $this->openDxpEntityManager->getRepository(JobRun::class)
            ->createQueryBuilder('jobRun')
            ->where('jobRun.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('jobRun.id', 'DESC')
            ->setMaxResults($limit);

        $running = $query->expr()->eq('jobRun.state', ':running');
        $query->setParameter('running', JobRunStates::RUNNING);

        if ($disposableContexts === []) {
            $query->andWhere($running);
        } else {
            $query->andWhere($query->expr()->orX(
                $running,
                $query->expr()->andX(
                    $query->expr()->in('jobRun.executionContext', ':disposableContexts'),
                    $query->expr()->in('jobRun.state', ':ended'),
                ),
            ))
                ->setParameter('disposableContexts', $disposableContexts)
                ->setParameter('ended', [JobRunStates::FINISHED, JobRunStates::FINISHED_WITH_ERRORS]);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @param list<int> $ids
     *
     * @return list<JobRun>
     *
     * @internal
     */
    public function getEndedJobRuns(int $ownerId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->openDxpEntityManager->getRepository(JobRun::class)
            ->createQueryBuilder('jobRun')
            ->where('jobRun.ownerId = :ownerId')
            ->andWhere('jobRun.id IN (:ids)')
            ->andWhere('jobRun.state != :running')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('ids', $ids)
            ->setParameter('running', JobRunStates::RUNNING)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{jobRuns: list<JobRun>, total: int}
     *
     * @internal
     */
    public function getJobRunsForPanel(JobRunFilter $filter, int $offset, int $limit): array
    {
        $query = $this->openDxpEntityManager->getRepository(JobRun::class)->createQueryBuilder('jobRun');

        if ($filter->ownerId !== null) {
            $query->andWhere('jobRun.ownerId = :ownerId')->setParameter('ownerId', $filter->ownerId);
        }

        if ($filter->withoutOwner) {
            $query->andWhere('jobRun.ownerId IS NULL');
        }

        if ($filter->jobName !== null) {
            $query->andWhere('jobRun.jobName = :jobName')->setParameter('jobName', $filter->jobName);
        }

        if ($filter->state !== null) {
            $query->andWhere('jobRun.state = :state')->setParameter('state', $filter->state);
        }

        if ($filter->startedFrom !== null) {
            $query->andWhere('jobRun.creationDate >= :startedFrom')
                ->setParameter('startedFrom', $filter->startedFrom);
        }

        if ($filter->startedUntil !== null) {
            $query->andWhere('jobRun.creationDate <= :startedUntil')
                ->setParameter('startedUntil', $filter->startedUntil);
        }

        $total = (int) (clone $query)->select('COUNT(jobRun.id)')->getQuery()->getSingleScalarResult();
        $jobRuns = $query->orderBy('jobRun.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['jobRuns' => $jobRuns, 'total' => $total];
    }
}
