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

namespace OpenDxp\Bundle\SeoBundle\Controller;

use Exception;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\Csv;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Logger;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/redirects')]
class RedirectsController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/list', name: 'opendxp_bundle_seo_redirects_redirects', methods: ['POST'])]
    public function redirectsAction(Request $request, RedirectHandler $redirectHandler): JsonResponse
    {
        // check permission for both update and listing
        $this->checkPermission('redirects');

        if ($request->request->has('data')) {
            $data = $this->decodeJson($request->request->getString('data'));

            if ($request->query->getString('xaction') === 'destroy') {

                $id = $data['id'] ?? null;
                if ($id) {
                    $redirect = Redirect::getById($id);
                    $redirect?->delete();
                }

                return $this->jsonResponse(['success' => true, 'data' => []]);
            }
            if ($request->query->getString('xaction') === 'update') {
                // save redirect
                $redirect = Redirect::getById($data['id']);

                if (!$redirect) {
                    return $this->jsonResponse(['success' => false]);
                }

                if ($data['target'] && $doc = Document::getByPath($data['target'])) {
                    $data['target'] = $doc->getId();
                }

                if (!$data['regex'] && $data['source']) {
                    $data['source'] = str_replace('+', ' ', $data['source']);
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget) && $doc = Document::getById((int)$redirectTarget)) {
                    $redirect->setTarget($doc->getRealFullPath());
                }

                return $this->jsonResponse(['data' => $redirect->getObjectVars(), 'success' => true]);
            }
            if ($request->query->getString('xaction') === 'create') {
                unset($data['id']);

                // save route
                $redirect = new Redirect();

                if (!empty($data['target']) && $doc = Document::getByPath($data['target'])) {
                    $data['target'] = $doc->getId();
                }

                if (isset($data['regex']) && !$data['regex'] && isset($data['source']) && $data['source']) {
                    $data['source'] = str_replace('+', ' ', $data['source']);
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget) && $doc = Document::getById((int)$redirectTarget)) {
                    $redirect->setTarget($doc->getRealFullPath());
                }

                return $this->jsonResponse(['data' => $redirect->getObjectVars(), 'success' => true]);
            }
        } else {
            // get list of routes
            $list = new Redirect\Listing();
            $list->setLimit($request->request->getInt('limit', 50));
            $list->setOffset($request->request->getInt('start'));

            $sortingSettings = QueryParams::extractSortingSettings([...$request->request->all(), ...$request->query->all()]);
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($filterValue = $request->request->getString('filter')) {
                if (is_numeric($filterValue)) {
                    $list->setCondition('id = ?', [$filterValue]);
                } elseif (preg_match('@^https?://@', $filterValue)) {
                    $dummyRequest = Request::create($filterValue);
                    $site = Site::getByDomain($dummyRequest->getHost());
                    $dummyResponse = $redirectHandler->checkForRedirect($dummyRequest, false, $site);
                    if ($dummyResponse && $redirectId = $dummyResponse->headers->get(RedirectHandler::RESPONSE_HEADER_NAME_ID)) {
                        $list->setCondition('id = ?', [$redirectId]);
                    } else {
                        // do not return any results
                        $list->setCondition('1 = 2');
                    }
                } else {
                    $list->setCondition('`source` LIKE ' . $list->quote('%' . $filterValue . '%') . ' OR `target` LIKE ' . $list->quote('%' . $filterValue . '%'));
                }
            }

            $list->load();

            $redirects = [];
            foreach ($list->getRedirects() as $redirect) {
                $link = $redirect->getTarget();
                if (is_numeric($link) && $doc = Document::getById((int)$link)) {
                    $redirect->setTarget($doc->getRealFullPath());
                }

                $redirects[] = $redirect->getObjectVars();
            }

            return $this->jsonResponse(['data' => $redirects, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->jsonResponse(['success' => false]);
    }

    #[Route('/csv-export', name: 'opendxp_bundle_seo_redirects_csvexport', methods: ['GET'])]
    public function csvExportAction(Csv $csv): Response
    {
        $this->checkPermission('redirects');

        $list = new Redirect\Listing();
        $list->setOrderKey('id');
        $list->setOrder('ASC');
        $list->load();

        $writer = $csv->createExportWriter($list);

        $response = new Response();
        $response->headers->set('Content-Encoding', 'none');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'redirects.csv'
        ));

        $response->setContent($writer->toString());

        return $response;
    }

    #[Route('/csv-import', name: 'opendxp_bundle_seo_redirects_csvimport', methods: ['POST'])]
    public function csvImportAction(Request $request, Csv $csv): Response
    {
        $this->checkPermission('redirects');

        /** @var UploadedFile|null $file */
        $file = $request->files->get('redirects');

        if (!$file) {
            throw new BadRequestHttpException('Missing file');
        }

        $result = $csv->import($file->getRealPath());

        return $this->jsonResponse([
            'success' => true,
            'data' => $result,
        ]);
    }

    #[Route('/cleanup', name: 'opendxp_bundle_seo_redirects_cleanup', methods: ['DELETE'])]
    public function cleanupAction(): JsonResponse
    {
        $this->checkPermission('redirects');

        try {
            $now = time();
            $expiredRedirects = new Redirect\Listing();
            $expiredRedirects->setCondition("expiry IS NOT NULL AND expiry < $now");
            $expiredRedirects = $expiredRedirects->load();

            foreach ($expiredRedirects as $expiredRedirect) {
                $expiredRedirect->delete();
            }

            return $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());

            return $this->jsonResponse(['success' => false]);
        }
    }

    #[Route('/get-statuscodes', name: 'opendxp_bundle_seo_redirects_statuscodes', methods: ['GET'])]
    public function statusCodesAction(): JsonResponse
    {
        $this->checkPermission('redirects');
        $statusCodes = Redirect::getStatusCodes();
        $codes = [];
        foreach ($statusCodes as $statusCode => $label) {
            $codes[] = [
                'statusCode' => $statusCode,
                'display' => "$label ($statusCode)",
            ];
        }
        $response = [
            'config' => [
                'statuscodes' => $codes,
            ],
        ];

        return $this->jsonResponse($response);
    }
}
