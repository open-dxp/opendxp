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

namespace OpenDxp\Bundle\XliffBundle\Controller;

use Exception;
use OpenDxp\Bundle\XliffBundle\ExportService\Exporter\ExporterInterface;
use OpenDxp\Bundle\XliffBundle\ExportService\ExportServiceInterface;
use OpenDxp\Bundle\XliffBundle\ImportDataExtractor\ImportDataExtractorInterface;
use OpenDxp\Bundle\XliffBundle\ImporterService\ImporterServiceInterface;
use OpenDxp\Bundle\XliffBundle\TranslationItemCollection\TranslationItemCollection;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Logger;
use OpenDxp\Model\Element;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/translation')]
class XliffTranslationController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @throws Exception
     */
    #[Route('/xliff-export', name: 'opendxp_bundle_xliff_translation_xliffexport', methods: ['POST'])]
    public function xliffExportAction(Request $request, ExportServiceInterface $exportService): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $this->validateFileId($request->request->getString('id'));
        $data = $this->decodeJson($request->request->getString('data'));
        $source = $request->request->getString('source');
        $target = $request->request->getString('target');

        $translationItems = new TranslationItemCollection();

        foreach ($data as $el) {
            $element = Element\Service::getElementById($el['type'], (int) $el['id']);
            $translationItems->addOpenDxpElement($element);
        }

        $exportService->exportTranslationItems($translationItems, $source, [$target], $id);

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    #[Route('/xliff-export-download', name: 'opendxp_bundle_xliff_translation_exportdownload', methods: ['GET'])]
    public function xliffExportDownloadAction(Request $request, ExporterInterface $translationExporter, ExportServiceInterface $exportService): BinaryFileResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $this->validateFileId($request->query->getString('id'));
        $exportFile = $exportService->getTranslationExporter()->getExportFilePath($id);

        $response = new BinaryFileResponse($exportFile);
        $response->headers->set('Content-Type', $translationExporter->getContentType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($exportFile));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/xliff-import-upload', name: 'opendxp_bundle_xliff_translation_xliffimportupload', methods: ['POST'])]
    public function xliffImportUploadAction(Request $request, ImportDataExtractorInterface $importDataExtractor): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $jobs = [];
        $id = uniqid();
        $importFile = $importDataExtractor->getImportFilePath($id);
        copy($_FILES['file']['tmp_name'], $importFile);

        $steps = $importDataExtractor->countSteps($id);

        for ($i = 0; $i < $steps; $i++) {
            $jobs[] = [[
                'url' => $this->generateUrl('opendxp_bundle_xliff_translation_xliffimportelement'),
                'method' => 'POST',
                'params' => [
                    'id' => $id,
                    'step' => $i,
                ],
            ]];
        }

        $response = $this->jsonResponse([
            'success' => true,
            'jobs' => $jobs,
            'id' => $id,
        ]);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/xliff-import-element', name: 'opendxp_bundle_xliff_translation_xliffimportelement', methods: ['POST'])]
    public function xliffImportElementAction(Request $request, ImportDataExtractorInterface $importDataExtractor, ImporterServiceInterface $importerService): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $this->validateFileId($request->request->getString('id'));
        $step = $request->request->getInt('step');

        try {
            $attributeSet = $importDataExtractor->extractElement($id, $step);
            if ($attributeSet) {
                $importerService->import($attributeSet);
            } else {
                Logger::warning(sprintf('Could not resolve element %s', $id));
            }
        } catch (Exception $e) {
            Logger::err($e->getMessage());

            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    private function validateFileId(string $id): string
    {
        if (!preg_match('/^[a-z0-9]+$/', $id)) {
            throw new BadRequestHttpException('Invalid file id');
        }

        return $id;
    }
}
