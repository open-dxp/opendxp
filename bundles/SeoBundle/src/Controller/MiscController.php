<?php

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

namespace OpenDxp\Bundle\SeoBundle\Controller;

use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Db;
use OpenDxp\Model\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;

class MiscController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/http-error-log', name: 'opendxp_bundle_seo_misc_httperrorlog', methods: ['POST'])]
    public function httpErrorLogAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        $db = Db::get();

        $limit = $request->request->getInt('limit');
        $offset = $request->request->getInt('start');
        $sortInfo = ($request->request->has('sort') ? json_decode($request->request->getString('sort'), true)[0] : []);
        $sort = $sortInfo['property'] ?? null;
        $dir = $sortInfo['direction'] ?? null;
        $filter = $request->request->getString('filter');
        if (!$limit) {
            $limit = 20;
        }
        if (!$offset) {
            $offset = 0;
        }
        if (!$sort || !in_array($sort, ['code', 'uri', 'date', 'count'])) {
            $sort = 'count';
        }
        if (!$dir || !in_array($dir, ['DESC', 'ASC'])) {
            $dir = 'DESC';
        }

        $qb = $db->createQueryBuilder()
            ->select('code', 'uri', '`count`', 'date')
            ->from('http_error_log')
            ->orderBy($sort, $dir)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($filter) {
            $qb->where('uri LIKE :filter OR code LIKE :filter OR parametersGet LIKE :filter')
               ->setParameter('filter', '%' . $filter . '%');
        }

        $logs = array_map(static function (array $log): array {
            // The admin offers to create a redirect for the path, on the site the request went to.
            $url = parse_url((string) $log['uri']);
            $site = isset($url['host']) ? Site::getByDomain($url['host']) : null;

            return [...$log, 'path' => urldecode($url['path'] ?? '/'), 'siteId' => $site?->getId()];
        }, $qb->executeQuery()->fetchAllAssociative());

        $countQb = $db->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('http_error_log');

        if ($filter) {
            $countQb->where('uri LIKE :filter OR code LIKE :filter OR parametersGet LIKE :filter')
                    ->setParameter('filter', '%' . $filter . '%');
        }

        $total = $countQb->executeQuery()->fetchOne();

        return $this->jsonResponse([
            'items' => $logs,
            'total' => $total,
            'success' => true,
        ]);
    }

    #[Route('/http-error-log-detail', name: 'opendxp_bundle_seo_misc_httperrorlogdetail', methods: ['GET'])]
    public function httpErrorLogDetailAction(Request $request, ?Profiler $profiler): Response
    {
        $this->checkPermission('http_errors');

        if ($profiler) {
            $profiler->disable();
        }

        $db = Db::get();
        $data = $db->fetchAssociative('SELECT * FROM http_error_log WHERE uri = ?', [$request->query->getString('uri')]);

        foreach ($data as $key => &$value) {
            if ($key === 'parametersGet') {
                $value = unserialize($value);
            }
        }

        return $this->render('@OpenDxpSeo/misc/http_error_log_detail.html.twig', ['data' => $data]);
    }

    #[Route('/http-error-log-entry', name: 'opendxp_bundle_seo_misc_httperrorlogentrydelete', methods: ['DELETE'])]
    public function httpErrorLogEntryDeleteAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        Db::get()->executeStatement(
            'DELETE FROM http_error_log WHERE uriHash = ?',
            [sha1($request->query->getString('uri'), true)],
        );

        return $this->jsonResponse(['success' => true]);
    }

    #[Route('/http-error-log-flush', name: 'opendxp_bundle_seo_misc_httperrorlogflush', methods: ['DELETE'])]
    public function httpErrorLogFlushAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        Db::get()->executeQuery('TRUNCATE TABLE http_error_log');

        return $this->jsonResponse([
            'success' => true,
        ]);
    }
}
