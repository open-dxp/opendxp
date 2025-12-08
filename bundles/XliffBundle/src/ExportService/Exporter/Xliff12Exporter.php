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

namespace OpenDxp\Bundle\XliffBundle\ExportService\Exporter;

use DOMDocument;
use OpenDxp;
use OpenDxp\Bundle\XliffBundle\AttributeSet\AttributeSet;
use OpenDxp\Bundle\XliffBundle\Escaper\Xliff12Escaper;
use OpenDxp\Bundle\XliffBundle\Event\Model\TranslationXliffEvent;
use OpenDxp\Bundle\XliffBundle\Event\XliffEvents;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Filesystem;

class Xliff12Exporter implements ExporterInterface
{
    const DELIMITER = '~-~';

    private ?SimpleXMLElement $xliffFile = null;

    public function __construct(private readonly Xliff12Escaper $xliffEscaper, protected Filesystem $filesystem)
    {
    }

    public function export(AttributeSet $attributeSet, ?string $exportId = null): string
    {
        $exportId = $exportId ?: uniqid();
        $exportFile = $this->getExportFilePath($exportId);

        $event = new TranslationXliffEvent($attributeSet);
        OpenDxp::getEventDispatcher()->dispatch($event, XliffEvents::XLIFF_ATTRIBUTE_SET_EXPORT);

        $attributeSet = $event->getAttributeSet();

        if ($attributeSet->isEmpty()) {
            return $exportFile;
        }

        $this->prepareExportFile($exportFile);

        foreach ($attributeSet->getTargetLanguages() as $targetLanguage) {
            $file = $this->xliffFile->addChild('file');
            $file->addAttribute('original', $attributeSet->getTranslationItem()->getType() . '-' . $attributeSet->getTranslationItem()->getId());
            $file->addAttribute('source-language', $attributeSet->getSourceLanguage());
            $file->addAttribute('target-language', $targetLanguage);
            $file->addAttribute('datatype', 'html');
            $file->addAttribute('tool', 'opendxp');
            $file->addAttribute('category', $attributeSet->getTranslationItem()->getType());

            $file->addChild('header');

            $body = $file->addChild('body');

            foreach ($attributeSet->getAttributes() as $attribute) {
                if ($attribute->isReadonly()) {
                    continue;
                }

                $targetContent = $attribute->getTargetContent()[$targetLanguage] ?? null;

                $this->addTransUnitNode($body, $attribute->getType() . self::DELIMITER . $attribute->getName(), $attribute->getContent(), $attributeSet->getSourceLanguage(), $targetContent, $targetLanguage);
            }
        }

        $this->xliffFile->asXML($exportFile);

        return $exportFile;
    }

    public function getExportFilePath(string $exportId): string
    {
        $exportFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/' . $exportId . '.xliff';
        if (!is_file($exportFile)) {
            // create initial xml file structure
            $this->filesystem->dumpFile($exportFile, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2"></xliff>');
        }

        return $exportFile;
    }

    protected function prepareExportFile(string $exportFilePath): void
    {
        if (!$this->xliffFile instanceof SimpleXMLElement) {
            $dom = new DOMDocument();
            $dom->loadXML(file_get_contents($exportFilePath));
            $this->xliffFile = simplexml_import_dom($dom);
        }
    }

    public function getContentType(): string
    {
        return 'application/x-xliff+xml';
    }

    protected function addTransUnitNode(SimpleXMLElement $xml, string $name, string $sourceContent, string $sourceLang, ?string $targetContent, string $targetLang): void
    {
        $transUnit = $xml->addChild('trans-unit');
        $transUnit->addAttribute('id', htmlentities($name));

        $sourceNode = $transUnit->addChild('source');
        $sourceNode->addAttribute('xmlns:xml:lang', $sourceLang);

        $node = dom_import_simplexml($sourceNode);
        $no = $node->ownerDocument;
        $f = $no->createDocumentFragment();
        $f->appendXML($this->xliffEscaper->escapeXliff($sourceContent));
        @$node->appendChild($f);

        if (!empty($targetContent)) {
            $targetNode = $transUnit->addChild('target');
            $targetNode->addAttribute('xmlns:xml:lang', $targetLang);

            $tNode = dom_import_simplexml($targetNode);
            $targetFragment = $no->createDocumentFragment();
            $targetFragment->appendXML($this->xliffEscaper->escapeXliff($targetContent));
            @$tNode->appendChild($targetFragment);
        }
    }
}
