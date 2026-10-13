<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Controller;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgent;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRunErrorLog;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Grid\JobRunGridInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunFilter;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Security\GenericExecutionEnginePermission;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Security\PermissionAttribute;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[Route('/job-run')]
final class JobRunController extends UserAwareController
{
    use JsonHelperTrait;

    private const int MENU_LIMIT = 30;

    private const int PANEL_LIMIT = 100;

    public function __construct(
        private readonly JobExecutionAgent $jobExecutionAgent,
        private readonly JobRunRepository $jobRunRepository,
        private readonly JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository,
        private readonly JobRunArtifactStorage $jobRunArtifactStorage,
        private readonly ExecutionContextInterface $executionContext,
        private readonly JobRunGridInterface $jobRunGrid,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/menu', name: 'opendxp_bundle_genericexecutionengine_jobrun_menu', methods: ['GET'])]
    public function menuAction(Request $request): JsonResponse
    {
        $userId = $this->getOpenDxpUser()->getId();
        $jobRuns = $this->jobRunRepository->getJobRunsForMenu(
            $userId,
            $this->executionContext->getDisposableContexts(),
            self::MENU_LIMIT,
        );
        $endedJobRuns = $this->jobRunRepository->getEndedJobRuns(
            $userId,
            array_map(intval(...), $request->query->all('ids')),
        );

        return $this->jsonResponse([
            'jobRuns' => array_map($this->jobRunGrid->convertJobRunToArray(...), $jobRuns),
            'endedJobRuns' => array_map($this->jobRunGrid->convertJobRunToArray(...), $endedJobRuns),
        ]);
    }

    #[Route('/list', name: 'opendxp_bundle_genericexecutionengine_jobrun_list', methods: ['GET'])]
    public function listAction(Request $request): JsonResponse
    {
        if (!$this->isAllowed(GenericExecutionEnginePermission::JobRun)) {
            throw new AccessDeniedHttpException('The user may not see the job run overview.');
        }

        $page = $this->jobRunRepository->getJobRunsForPanel(
            $this->createFilter($request),
            max($request->query->getInt('start'), 0),
            min(max($request->query->getInt('limit', self::PANEL_LIMIT), 1), self::PANEL_LIMIT),
        );

        return $this->jsonResponse([
            'jobRuns' => array_map($this->jobRunGrid->convertJobRunToArray(...), $page['jobRuns']),
            'total' => $page['total'],
        ]);
    }

    #[Route('/job-names', name: 'opendxp_bundle_genericexecutionengine_jobrun_jobnames', methods: ['GET'])]
    public function jobNamesAction(): JsonResponse
    {
        if (!$this->isAllowed(GenericExecutionEnginePermission::JobRun)) {
            throw new AccessDeniedHttpException('The user may not see the job run overview.');
        }

        $ownerId = $this->isAllowed(GenericExecutionEnginePermission::SeeAllJobRuns)
            ? null
            : $this->getOpenDxpUser()->getId();

        $jobNames = [];
        foreach ($this->jobRunRepository->getJobNames($ownerId) as $row) {
            $domain = $this->executionContext->getTranslationDomain($row['executionContext']);
            $jobNames[$row['jobName']] ??= [
                'name' => $row['jobName'],
                'label' => $this->translator->trans($row['jobName'], [], $domain),
            ];
        }

        return $this->jsonResponse(['jobNames' => array_values($jobNames)]);
    }

    #[Route('/errors', name: 'opendxp_bundle_genericexecutionengine_jobrun_errors', methods: ['GET'])]
    public function errorsAction(Request $request): JsonResponse
    {
        $jobRunId = $this->getVisibleJobRun($request->query->getInt('id'))->getId();
        $errors = $this->jobRunErrorLogRepository->getLogsByJobRunId(
            $jobRunId,
            orderBy: ['id' => 'ASC'],
            limit: min(max($request->query->getInt('limit', self::PANEL_LIMIT), 1), self::PANEL_LIMIT),
            offset: max($request->query->getInt('start'), 0),
        );

        return $this->jsonResponse([
            'errors' => array_map(static fn (JobRunErrorLog $error): array => [
                'step' => $error->getStepNumber(),
                'elementId' => $error->getElementId(),
                'message' => $error->getErrorMessage(),
            ], $errors),
            'total' => $this->jobRunErrorLogRepository->getTotalCountByJobRunId($jobRunId),
        ]);
    }

    #[Route('/cancel', name: 'opendxp_bundle_genericexecutionengine_jobrun_cancel', methods: ['POST'])]
    public function cancelAction(Request $request): JsonResponse
    {
        $jobRun = $this->getVisibleJobRun($request->request->getInt('id'));
        $this->jobExecutionAgent->cancelJobRun($jobRun->getId());

        return $this->jsonResponse(['success' => true]);
    }

    #[Route('/delete', name: 'opendxp_bundle_genericexecutionengine_jobrun_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request): JsonResponse
    {
        $jobRun = $this->getVisibleJobRun($request->query->getInt('id'));
        if ($jobRun->getState() === JobRunStates::RUNNING) {
            throw new ConflictHttpException(sprintf('The job run %d still runs.', $jobRun->getId()));
        }

        $this->jobExecutionAgent->deleteJobRun($jobRun->getId());

        return $this->jsonResponse(['success' => true]);
    }

    #[Route('/download', name: 'opendxp_bundle_genericexecutionengine_jobrun_download', methods: ['GET'])]
    public function downloadAction(Request $request): StreamedResponse
    {
        $jobRun = $this->getVisibleJobRun($request->query->getInt('id'));
        $jobRunId = $jobRun->getId();
        $filename = $request->query->getString('artifact');

        $hasArtifact = $jobRun->getResult()?->hasArtifact($filename) ?? false;
        if (!$hasArtifact || !$this->jobRunArtifactStorage->has($jobRunId, $filename)) {
            throw new NotFoundHttpException(sprintf('The job run %d has no artifact "%s".', $jobRunId, $filename));
        }

        $response = new StreamedResponse(function () use ($jobRunId, $filename): void {
            $stream = $this->jobRunArtifactStorage->getStream($jobRunId, $filename);
            fpassthru($stream);
            fclose($stream);

            $this->jobExecutionAgent->addDownloadedArtifact($jobRunId, $filename);
        });
        $response->headers->set('Content-Type', $this->jobRunArtifactStorage->getMimeType($jobRunId, $filename));
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            $this->toAsciiFilename($filename),
        ));

        return $response;
    }

    private function getVisibleJobRun(int $id): JobRun
    {
        try {
            $jobRun = $this->jobRunRepository->getJobRunById($id, forceReload: true);
        } catch (NotFoundException) {
            throw new NotFoundHttpException(sprintf('The job run %d does not exist.', $id));
        }

        // A hidden job run answers like a missing one, so its ID reveals nothing.
        if (!$this->isVisible($jobRun)) {
            throw new NotFoundHttpException(sprintf('The job run %d does not exist.', $id));
        }

        return $jobRun;
    }

    private function isVisible(JobRun $jobRun): bool
    {
        return $jobRun->getOwnerId() === $this->getOpenDxpUser()->getId()
            || $this->isAllowed(GenericExecutionEnginePermission::SeeAllJobRuns);
    }

    private function isAllowed(GenericExecutionEnginePermission $permission): bool
    {
        return $this->getOpenDxpUser()->isAllowed(PermissionAttribute::for($permission->value));
    }

    private function createFilter(Request $request): JobRunFilter
    {
        $state = $request->query->getString('state');
        $jobName = $request->query->getString('job');
        $owner = $request->query->getString('owner');

        $ownerId = $this->getOpenDxpUser()->getId();
        $withoutOwner = false;
        if ($this->isAllowed(GenericExecutionEnginePermission::SeeAllJobRuns)) {
            $withoutOwner = $owner === 'system';
            $ownerId = $owner === '' || $withoutOwner ? null : (int) $owner;
        }

        return new JobRunFilter(
            ownerId: $ownerId,
            withoutOwner: $withoutOwner,
            jobName: $jobName === '' ? null : $jobName,
            state: $state === '' ? null : JobRunStates::tryFrom($state),
            startedFrom: $request->query->has('from') ? $request->query->getInt('from') : null,
            startedUntil: $request->query->has('until') ? $request->query->getInt('until') : null,
        );
    }

    private function toAsciiFilename(string $filename): string
    {
        return preg_replace('/[^\x20-\x7e]|[\/\\\\%"]/', '_', $filename) ?? 'download';
    }
}
