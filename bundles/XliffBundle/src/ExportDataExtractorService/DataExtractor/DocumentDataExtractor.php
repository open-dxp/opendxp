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

namespace OpenDxp\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor;

use Exception;
use OpenDxp\Bundle\XliffBundle\AttributeSet\Attribute;
use OpenDxp\Bundle\XliffBundle\AttributeSet\AttributeSet;
use OpenDxp\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;
use OpenDxp\Document\Editable\EditableUsageResolver;
use OpenDxp\Model\Document;
use OpenDxp\Model\Property;

class DocumentDataExtractor extends AbstractElementDataExtractor
{
    public const array EXPORTABLE_TAGS = ['wysiwyg', 'input', 'textarea', 'image', 'link'];

    public function __construct(private readonly EditableUsageResolver $EditableUsageResolver)
    {
    }

    /**
     * @param string[] $targetLanguages
     *
     * @throws Exception
     */
    #[\Override]
    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages): AttributeSet
    {
        $document = $translationItem->getElement();

        $result = parent::extract($translationItem, $sourceLanguage, $targetLanguages);

        if (!$document instanceof Document) {
            throw new Exception('only documents allowed');
        }

        $this
            ->addDocumentEditables($document, $result)
            ->addSettings($document, $result);

        return $result;
    }

    protected function addDocumentEditables(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        $editables = [];
        $service = new Document\Service;

        $translations = $service->getTranslations($document);

        $this->resetSourceDocument($document, $result, $translations);

        if ($document instanceof Document\PageSnippet) {
            $editableNames = $this->EditableUsageResolver->getUsedEditableNames($document);
            foreach ($editableNames as $editableName) {
                if ($editable = $document->getEditable($editableName)) {
                    $editables[] = $editable;
                }
            }
        }

        foreach ($editables as $editable) {
            if (in_array($editable->getType(), self::EXPORTABLE_TAGS)) {
                if ($editable instanceof Document\Editable\Image || $editable instanceof Document\Editable\Link) {
                    $content = $editable->getText();
                } else {
                    $content = $editable->getData();
                }

                $targetContent = [];
                foreach ($result->getTargetLanguages() as $targetLanguage) {
                    if (isset($translations[$targetLanguage])) {
                        $targetDocument = Document::getById($translations[$targetLanguage]);

                        if ($targetDocument instanceof  Document\PageSnippet) {
                            $targetTag = $targetDocument->getEditable($editable->getName());
                            if ($targetTag instanceof Document\Editable\Image || $targetTag instanceof Document\Editable\Link) {
                                $targetContent[$targetLanguage] = $targetTag->getText();
                            } elseif ($targetTag instanceof \OpenDxp\Model\Document\Editable) {
                                $targetContent[$targetLanguage] = $targetTag->getData();
                            }
                        }
                    }
                }

                if (is_string($content)) {
                    $contentCheck = trim(strip_tags($content));
                    if (!empty($contentCheck)) {
                        $result->addAttribute(Attribute::TYPE_TAG, $editable->getName(), $content, false, $targetContent);
                    }
                }
            }
        }

        return $this;
    }

    protected function addSettings(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        $service = new Document\Service;
        $translations = $service->getTranslations($document);

        $this->resetSourceDocument($document, $result, $translations);

        if ($document instanceof Document\Page) {
            $data = [
                'title' => $document->getTitle(),
                'description' => $document->getDescription(),
            ];

            $targetData = [];
            foreach ($result->getTargetLanguages() as $targetLanguage) {
                if (isset($translations[$targetLanguage])) {
                    $targetDocument = Document::getById($translations[$targetLanguage]);

                    if ($targetDocument instanceof  Document\Page) {
                        $targetData['title'][$targetLanguage] = $targetDocument->getTitle();
                        $targetData['description'][$targetLanguage] = $targetDocument->getDescription();
                    }
                }
            }

            foreach ($data as $key => $content) {
                if (!empty(trim($content))) {
                    $result->addAttribute(Attribute::TYPE_SETTINGS, $key, $content, false, $targetData[$key] ?? []);
                }
            }
        }

        return $this;
    }

    #[\Override]
    protected function doExportProperty(Property $property): bool
    {
        return
            parent::doExportProperty($property) &&
            !in_array(
                $property->getName(),
                [
                    'language',
                    'navigation_target',
                    'navigation_exclude',
                    'navigation_class',
                    'navigation_anchor',
                    'navigation_parameters',
                    'navigation_relation',
                    'navigation_accesskey',
                    'navigation_tabindex',
                ]
            );
    }

    private function resetSourceDocument(Document &$document, AttributeSet $result, array $translations): void
    {
        $sourceDocumentId = $translations[$result->getSourceLanguage()] ?? false;

        if ($sourceDocumentId) {
            $sourceDocument = Document::getById((int) $sourceDocumentId);

            if ($sourceDocument instanceof Document\PageSnippet) {
                $document = $sourceDocument;
            }
        }
    }
}
