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

namespace OpenDxp\Bundle\XliffBundle\ExportService;

use OpenDxp\Bundle\XliffBundle\ExportDataExtractorService\ExportDataExtractorServiceInterface;
use OpenDxp\Bundle\XliffBundle\ExportService\Exporter\ExporterInterface;
use OpenDxp\Bundle\XliffBundle\TranslationItemCollection\TranslationItemCollection;

class ExportService implements ExportServiceInterface
{
    /**
     * ExportService constructor.
     *
     */
    public function __construct(private ExportDataExtractorServiceInterface $exportDataExtractorService, private ExporterInterface $translationExporter)
    {
    }

    public function exportTranslationItems(TranslationItemCollection $translationItems, string $sourceLanguage, array $targetLanguages, ?string $exportId = null): string
    {
        $exportId = empty($exportId) ? uniqid() : $exportId;

        foreach ($translationItems->getItems() as $item) {
            $attributeSet = $this->getExportDataExtractorService()->extract($item, $sourceLanguage, $targetLanguages);
            $this->getTranslationExporter()->export($attributeSet, $exportId);
        }

        return $exportId;
    }

    public function getExportDataExtractorService(): ExportDataExtractorServiceInterface
    {
        return $this->exportDataExtractorService;
    }

    public function setExportDataExtractorService(ExportDataExtractorServiceInterface $exportDataExtractorService): ExportService
    {
        $this->exportDataExtractorService = $exportDataExtractorService;

        return $this;
    }

    public function getTranslationExporter(): ExporterInterface
    {
        return $this->translationExporter;
    }

    public function setTranslationExporter(ExporterInterface $translationExporter): ExportService
    {
        $this->translationExporter = $translationExporter;

        return $this;
    }
}
