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

namespace OpenDxp\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor;

use Exception;
use OpenDxp\Bundle\XliffBundle\AttributeSet\Attribute;
use OpenDxp\Bundle\XliffBundle\AttributeSet\AttributeSet;
use OpenDxp\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Property;
use OpenDxp\Model\Property\PropertyType;

abstract class AbstractElementDataExtractor implements DataExtractorInterface
{
    protected function createResultInstance(TranslationItem $translationItem): AttributeSet
    {
        return new AttributeSet($translationItem);
    }

    /**
     * @param string[] $targetLanguages
     *
     * @throws Exception
     */
    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages): AttributeSet
    {
        $element = $translationItem->getElement();

        $result = $this
                    ->createResultInstance($translationItem)
                    ->setSourceLanguage($sourceLanguage)
                    ->setTargetLanguages($targetLanguages);

        $this->addProperties($element, $result);

        return $result;
    }

    protected function doExportProperty(Property $property): bool
    {
        return $property->getType() === PropertyType::Text->value && !$property->isInherited() && !empty($property->getData());
    }

    protected function addProperties(ElementInterface $element, AttributeSet $result): void
    {
        foreach ($element->getProperties() ?: [] as $property) {
            if ($this->doExportProperty($property)) {
                $result->addAttribute(Attribute::TYPE_PROPERTY, $property->getName(), $property->getData());
            }
        }
    }
}
