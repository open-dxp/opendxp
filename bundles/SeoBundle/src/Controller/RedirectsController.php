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

namespace OpenDxp\Bundle\SeoBundle\Controller;

use Exception;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\Csv;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectValidator;
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

    /**
     * The fields an editor may set. Everything else, like the owner or the dates, is kept by the model.
     */
    private const array EDITABLE_FIELDS = [
        'type', 'source', 'sourceSite', 'target', 'targetSite', 'statusCode', 'priority', 'regex',
        'passThroughParameters', 'passThroughPath', 'active', 'validFrom', 'expiry', 'protected',
    ];

    #[Route('/list', name: 'opendxp_bundle_seo_redirects_redirects', methods: ['POST'])]
    public function redirectsAction(Request $request, RedirectHandler $redirectHandler, RedirectValidator $validator): JsonResponse
    {
        // check permission for both update and listing
        $this->checkPermission('redirects');

        if ($request->request->has('data')) {
            $data = $this->decodeJson($request->request->getString('data'));

            return match ($request->query->getString('xaction')) {
                'destroy' => $this->deleteRedirect($data),
                'update' => $this->saveRedirect(Redirect::getById((int) ($data['id'] ?? 0)), $data, $validator),
                'create' => $this->saveRedirect(new Redirect(), $data, $validator),
                default => $this->jsonResponse(['success' => false]),
            };
        }

        // get list of routes
        $list = new Redirect\Listing();
        $list->setLimit($request->request->getInt('limit', 50));
        $list->setOffset($request->request->getInt('start'));

        $sortingSettings = QueryParams::extractSortingSettings([...$request->request->all(), ...$request->query->all()]);
        if ($sortingSettings['orderKey']) {
            $list->setOrderKey($sortingSettings['orderKey']);
            $list->setOrder($sortingSettings['order']);
        }

        $conditions = $this->mayManageProtected() ? [] : ['protected = 0'];
        $variables = [];

        if ($filterValue = $request->request->getString('filter')) {
            if (is_numeric($filterValue)) {
                $conditions[] = 'id = ?';
                $variables[] = $filterValue;
            } elseif (preg_match('@^https?://@', $filterValue)) {
                $dummyRequest = Request::create($filterValue);
                $site = Site::getByDomain($dummyRequest->getHost());
                $dummyResponse = $redirectHandler->checkForDomainRedirect($dummyRequest)
                    ?? $redirectHandler->checkForRedirect($dummyRequest, false, $site);

                $conditions[] = 'id = ?';
                $variables[] = (int) $dummyResponse?->headers->get(RedirectHandler::RESPONSE_HEADER_NAME_ID);
            } else {
                $conditions[] = '(`source` LIKE ? OR `target` LIKE ?)';
                $variables[] = '%' . $filterValue . '%';
                $variables[] = '%' . $filterValue . '%';
            }
        }

        if ($conditions !== []) {
            $list->setCondition(implode(' AND ', $conditions), $variables);
        }

        $list->load();

        $redirects = [];
        foreach ($list->getRedirects() as $redirect) {
            $redirects[] = $this->redirectData($redirect);
        }

        return $this->jsonResponse(['data' => $redirects, 'success' => true, 'total' => $list->getTotalCount()]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveRedirect(?Redirect $redirect, array $data, RedirectValidator $validator): JsonResponse
    {
        if (!$redirect instanceof Redirect) {
            return $this->jsonResponse(['success' => false]);
        }

        $mayManageProtected = $this->mayManageProtected();
        if ($redirect->isProtected() && !$mayManageProtected) {
            throw $this->createAccessDeniedException('Only users with the permission redirects_protected change a protected redirect.');
        }

        $values = array_intersect_key($data, array_flip(self::EDITABLE_FIELDS));
        if (!$mayManageProtected) {
            unset($values['protected']);
        }

        if (!empty($values['target']) && $doc = Document::getByPath($values['target'])) {
            $values['target'] = $doc->getId();
        }

        if (empty($values['regex'] ?? $redirect->getRegex()) && !empty($values['source'])) {
            $values['source'] = str_replace('+', ' ', $values['source']);
        }

        $redirect->setValues($values);

        $validation = $validator->validate($redirect, $mayManageProtected);
        if (!$validation->isValid()) {
            return $this->jsonResponse(['success' => false, 'errors' => $validation->errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $redirect->save();

        return $this->jsonResponse(['data' => $this->redirectData($redirect), 'success' => true, 'warnings' => $validation->warnings]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deleteRedirect(array $data): JsonResponse
    {
        $redirect = Redirect::getById((int) ($data['id'] ?? 0));

        if ($redirect?->isProtected() && !$this->mayManageProtected()) {
            throw $this->createAccessDeniedException('Only users with the permission redirects_protected delete a protected redirect.');
        }

        $redirect?->delete();

        return $this->jsonResponse(['success' => true, 'data' => []]);
    }

    /**
     * @return array<string, mixed>
     */
    private function redirectData(Redirect $redirect): array
    {
        $data = $redirect->getObjectVars();

        $target = $redirect->getTarget();
        if (is_numeric($target) && $doc = Document::getById((int) $target)) {
            $data['target'] = $doc->getRealFullPath();
        }

        return $data;
    }

    private function mayManageProtected(): bool
    {
        return (bool) $this->getOpenDxpUser()?->isAllowed('redirects_protected');
    }

    #[Route('/csv-export', name: 'opendxp_bundle_seo_redirects_csvexport', methods: ['GET'])]
    public function csvExportAction(Csv $csv): Response
    {
        $this->checkPermission('redirects');

        $list = new Redirect\Listing();
        $list->setOrderKey('id');
        $list->setOrder('ASC');
        if (!$this->mayManageProtected()) {
            $list->setCondition('protected = 0');
        }
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

        $result = $csv->import($file->getRealPath(), $this->mayManageProtected());

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
            $expiredRedirects->setCondition("expiry IS NOT NULL AND expiry < $now" . ($this->mayManageProtected() ? '' : ' AND protected = 0'));
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
