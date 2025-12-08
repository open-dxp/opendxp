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

namespace OpenDxp\Bundle\XliffBundle\ImportDataExtractor;

use Exception;
use Locale;
use OpenDxp\Bundle\XliffBundle\AttributeSet\AttributeSet;
use OpenDxp\Bundle\XliffBundle\Escaper\Xliff12Escaper;
use OpenDxp\Bundle\XliffBundle\ExportService\Exporter\Xliff12Exporter;
use OpenDxp\Bundle\XliffBundle\ImportDataExtractor\TranslationItemResolver\TranslationItemResolverInterface;
use OpenDxp\Tool;
use SimpleXMLElement;

class Xliff12DataExtractor implements ImportDataExtractorInterface
{
    public function __construct(protected Xliff12Escaper $xliffEscaper, protected TranslationItemResolverInterface $translationItemResolver)
    {
    }

    public function extractElement(string $importId, int $stepId): ?AttributeSet
    {
        $xliff = $this->loadFile($importId);

        $file = $xliff->file[$stepId];

        $target = $file['target-language'];

        // see https://en.wikipedia.org/wiki/IETF_language_tag
        $target = str_replace('-', '_', (string)$target);
        if (!Tool::isValidLanguage($target)) {
            $target = Locale::getPrimaryLanguage($target);
        }
        if (!Tool::isValidLanguage($target)) {
            throw new Exception(sprintf('invalid language %s', $file['target-language']));
        }

        [$type, $id] = explode('-', (string)$file['original']);

        $translationItem = $this->translationItemResolver->resolve($type, $id);

        if (empty($translationItem)) {
            return null;
        }

        $attributeSet = new AttributeSet($translationItem);
        $attributeSet->setTargetLanguages([$target]);
        if (!empty($file['source-language'])) {
            $attributeSet->setSourceLanguage((string)$file['source-language']);
        }

        foreach ($file->body->{'trans-unit'} as $transUnit) {
            [$type, $name] = explode(Xliff12Exporter::DELIMITER, (string)$transUnit['id']);
            if (!property_exists($transUnit, 'target')) {
                continue;
            }
            if ($transUnit->target === null) {
                continue;
            }

            $content = $transUnit->target->asXml();
            $content = $this->xliffEscaper->unescapeXliff($content);

            $attributeSet->addAttribute($type, $name, $content);
        }

        return $attributeSet;
    }

    public function getImportFilePath(string $importId): string
    {
        return OPENDXP_SYSTEM_TEMP_DIRECTORY . '/' . $importId . '.xliff';
    }

    public function countSteps(string $importId): int
    {
        $xliff = $this->loadFile($importId);

        return count($xliff->file);
    }

    /**
     * @throws Exception
     */
    private function loadFile(string $importId): SimpleXMLElement
    {
        return simplexml_load_file($this->getImportFilePath($importId), null, LIBXML_NOCDATA);
    }
}
