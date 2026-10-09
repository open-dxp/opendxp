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

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\Csv;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectGridListingFactory;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectValidator;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Db;
use OpenDxp\Logger;
use OpenDxp\Model\Document;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * The fields an editor may set. The model keeps everything else, like the owner or the dates.
     */
    private const array EDITABLE_FIELDS = [
        'type', 'source', 'sourceSite', 'target', 'targetSite', 'statusCode', 'priority', 'regex',
        'passThroughParameters', 'passThroughPath', 'active', 'validFrom', 'expiry', 'protected',
    ];

    /**
     * The grid sends an empty field as null. These fields keep the value of the redirect then.
     */
    private const array FIELDS_WITHOUT_NULL = [
        'type', 'statusCode', 'priority', 'passThroughParameters', 'passThroughPath', 'active', 'protected',
    ];

    #[Route('/list', name: 'opendxp_bundle_seo_redirects_redirects', methods: ['POST'])]
    public function redirectsAction(
        Request $request,
        RedirectGridListingFactory $listingFactory,
        RedirectValidator $validator,
    ): JsonResponse {
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

        $list = $listingFactory->create(
            [...$request->request->all(), ...$request->query->all()],
            $this->mayManageProtected(),
        );
        $list->setLimit($request->request->getInt('limit', 50));
        $list->setOffset($request->request->getInt('start'));

        $list->load();

        $hits = $this->hitsOf(array_map(
            static fn (Redirect $redirect): int => (int) $redirect->getId(),
            $list->getRedirects(),
        ));

        $redirects = [];
        foreach ($list->getRedirects() as $redirect) {
            $redirects[] = [
                ...$this->redirectData($redirect),
                ...($hits[$redirect->getId()] ?? ['hits' => 0, 'lastHit' => null]),
            ];
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
            throw $this->createAccessDeniedException(
                'Only users with the permission redirects_protected change a protected redirect.',
            );
        }

        $values = array_filter(
            array_intersect_key($data, array_flip(self::EDITABLE_FIELDS)),
            static fn (mixed $value, string $field): bool => ($value !== null && $value !== '')
                || !in_array($field, self::FIELDS_WITHOUT_NULL, true),
            ARRAY_FILTER_USE_BOTH,
        );

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

        $result = $validator->validate($redirect, $mayManageProtected);

        // The serializer of the admin writes into the objects it encodes, which a readonly object refuses.
        $errors = array_map(get_object_vars(...), $result->errors);
        $warnings = array_map(get_object_vars(...), $result->warnings);

        if (!$result->isValid()) {
            // The admin reports a failed request as a technical error, so a refused redirect is a regular answer.
            return $this->jsonResponse(['success' => false, 'errors' => $errors]);
        }

        $redirect->save();

        return $this->jsonResponse([
            'data' => $this->redirectData($redirect),
            'success' => true,
            'warnings' => $warnings,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deleteRedirect(array $data): JsonResponse
    {
        $redirect = Redirect::getById((int) ($data['id'] ?? 0));

        if ($redirect?->isProtected() && !$this->mayManageProtected()) {
            throw $this->createAccessDeniedException(
                'Only users with the permission redirects_protected delete a protected redirect.',
            );
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

    /**
     * @param list<int> $ids
     *
     * @return array<int, array{hits: int, lastHit: ?int}>
     */
    private function hitsOf(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $hits = [];
        $rows = Db::get()->fetchAllAssociative(
            'SELECT redirectId, hits, lastHit FROM redirect_hits WHERE redirectId IN (?)',
            [$ids],
            [ArrayParameterType::INTEGER],
        );
        foreach ($rows as $row) {
            $hits[(int) $row['redirectId']] = [
                'hits' => (int) $row['hits'],
                'lastHit' => $row['lastHit'] === null ? null : (int) $row['lastHit'],
            ];
        }

        return $hits;
    }

    private function mayManageProtected(): bool
    {
        return (bool) $this->getOpenDxpUser()?->isAllowed('redirects_protected');
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
            $condition = sprintf('expiry IS NOT NULL AND expiry <= %d', time());
            if (!$this->mayManageProtected()) {
                $condition .= ' AND protected = 0';
            }

            $expiredRedirects = new Redirect\Listing();
            $expiredRedirects->setCondition($condition);
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
                'countHits' => $this->getParameter('opendxp_seo.redirects')['count_hits'],
            ],
        ];

        return $this->jsonResponse($response);
    }
}
