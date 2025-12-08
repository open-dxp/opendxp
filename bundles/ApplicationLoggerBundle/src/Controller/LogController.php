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

namespace OpenDxp\Bundle\ApplicationLoggerBundle\Controller;

use Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
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
    public function showAction(Request $request, Connection $db): JsonResponse
    {
        $requestSource = $request->request;

        $this->checkPermission('application_logging');

        $qb = $db->createQueryBuilder();
        $qb
            ->select('*')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->setFirstResult($requestSource->getInt('start', 0))
            ->setMaxResults($requestSource->getInt('limit', 50));

        $qb->orderBy('id', 'DESC');

        $sortingSettings = \OpenDxp\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings([...$request->request->all(), ...$request->query->all()]);

        if ($sortingSettings['orderKey']) {
            $qb->orderBy($db->quoteIdentifier($sortingSettings['orderKey']), $sortingSettings['order']);
        }

        $priority = $requestSource->getString('priority');
        if (!empty($priority)) {
            $qb->andWhere($qb->expr()->eq('priority', ':priority'));
            $qb->setParameter('priority', $priority);
        }

        if ($fromDate = $this->parseDateObject($requestSource->getString('fromDate'), $requestSource->getString('fromTime'))) {
            $qb->andWhere('timestamp > :fromDate');
            $qb->setParameter('fromDate', $fromDate, Types::DATETIME_MUTABLE);
        }

        if ($toDate = $this->parseDateObject($requestSource->getString('toDate'), $requestSource->getString('toTime'))) {
            $qb->andWhere('timestamp <= :toDate');
            $qb->setParameter('toDate', $toDate, Types::DATETIME_MUTABLE);
        }

        if (!empty($component = $requestSource->getString('component'))) {
            $qb->andWhere('component = ' . $qb->createNamedParameter($component));
        }

        if (!empty($relatedObject = $requestSource->getString('relatedobject'))) {
            $qb->andWhere('relatedobject = ' . $qb->createNamedParameter($relatedObject));
        }

        if (!empty($message = $requestSource->getString('message'))) {
            $qb->andWhere('message LIKE ' . $qb->createNamedParameter('%' . $message . '%'));
        }

        if (!empty($pid = $requestSource->getInt('pid'))) {
            $qb->andWhere('pid LIKE ' . $qb->createNamedParameter('%' . $pid . '%'));
        }

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

    private function parseDateObject(?string $date, ?string $time): ?DateTime
    {
        if (empty($date)) {
            return null;
        }

        $pattern = '/^(?P<date>\d{4}\-\d{2}\-\d{2})T(?P<time>\d{2}:\d{2}:\d{2})$/';

        $dateTime = null;
        if (preg_match($pattern, $date, $dateMatches)) {
            if (!empty($time) && preg_match($pattern, $time, $timeMatches)) {
                $dateTime = new DateTime(sprintf('%sT%s', $dateMatches['date'], $timeMatches['time']));
            } else {
                $dateTime = new DateTime($date);
            }
        }

        return $dateTime;
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
