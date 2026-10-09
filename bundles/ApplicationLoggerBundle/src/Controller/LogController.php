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

namespace OpenDxp\Bundle\ApplicationLoggerBundle\Controller;

use Carbon\Carbon;
use OpenDxp\Bundle\ApplicationLoggerBundle\Grid\LogGridQueryFactory;
use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use OpenDxp\Controller\KernelControllerEventInterface;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Tool\Storage;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class LogController extends UserAwareController implements KernelControllerEventInterface
{
    use JsonHelperTrait;

    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$this->getOpenDxpUser()->isAllowed('application_logging')) {
            throw new AccessDeniedHttpException("Permission denied, user needs 'application_logging' permission.");
        }
    }

    #[Route('/log/show', name: 'opendxp_admin_bundle_applicationlogger_log_show', methods: ['POST'])]
    public function showAction(Request $request, LogGridQueryFactory $queryFactory): JsonResponse
    {
        $requestSource = $request->request;

        $this->checkPermission('application_logging');

        $qb = $queryFactory->create([...$request->request->all(), ...$request->query->all()]);
        $qb
            ->setFirstResult($requestSource->getInt('start', 0))
            ->setMaxResults($requestSource->getInt('limit', 50));

        $totalQb = clone $qb;
        $totalQb->setMaxResults(null)
            ->setFirstResult(0)
            ->select('COUNT(id) as count');
        $total = $totalQb->executeQuery()->fetchAssociative();
        $total = (int) $total['count'];

        $stmt = $qb->executeQuery();
        $result = $stmt->fetchAllAssociative();

        $logEntries = [];
        foreach ($result as $row) {
            $fileobject = null;
            if ($row['fileobject']) {
                $fileobject = str_replace(OPENDXP_PROJECT_ROOT, '', $row['fileobject']);
            }

            $carbonTs = new Carbon($row['timestamp'], 'UTC');
            $logEntry = [
                'id' => $row['id'],
                'pid' => $row['pid'],
                'message' => $row['message'],
                'date' => $row['timestamp'],
                'timestamp' => $carbonTs->getTimestamp(),
                'priority' => $row['priority'],
                'fileobject' => $fileobject,
                'relatedobject' => $row['relatedobject'],
                'relatedobjecttype' => $row['relatedobjecttype'],
                'component' => $row['component'],
                'source' => $row['source'],
            ];

            $logEntries[] = $logEntry;
        }

        return $this->jsonResponse([
            'p_totalCount' => $total,
            'p_results' => $logEntries,
        ]);
    }

    #[Route('/log/priority-json', name: 'opendxp_admin_bundle_applicationlogger_log_priorityjson', methods: ['GET'])]
    public function priorityJsonAction(Request $request): JsonResponse
    {
        $this->checkPermission('application_logging');

        $priorities[] = ['key' => '', 'value' => '-'];
        foreach (ApplicationLoggerDb::getPriorities() as $key => $p) {
            $priorities[] = ['key' => $key, 'value' => $p];
        }

        return $this->jsonResponse(['priorities' => $priorities]);
    }

    #[Route('/log/component-json', name: 'opendxp_admin_bundle_applicationlogger_log_componentjson', methods: ['GET'])]
    public function componentJsonAction(Request $request): JsonResponse
    {
        $this->checkPermission('application_logging');

        $components[] = ['key' => '', 'value' => '-'];
        foreach (ApplicationLoggerDb::getComponents() as $p) {
            $components[] = ['key' => $p, 'value' => $p];
        }

        return $this->jsonResponse(['components' => $components]);
    }

    #[Route('/log/show-file-object', name: 'opendxp_admin_bundle_applicationlogger_log_showfileobject', methods: ['GET'])]
    public function showFileObjectAction(Request $request): StreamedResponse
    {
        $this->checkPermission('application_logging');

        $filePath = $request->query->getString('filePath');
        $storage = Storage::get('application_log');

        if ($storage->fileExists($filePath)) {
            $fileData = $storage->readStream($filePath);
            $response = new StreamedResponse(
                static function () use ($fileData): void {
                    echo stream_get_contents($fileData);
                }
            );
            $response->headers->set('Content-Type', 'text/plain');

            return $response;
        }

        throw new FileNotFoundException($filePath);
    }
}
