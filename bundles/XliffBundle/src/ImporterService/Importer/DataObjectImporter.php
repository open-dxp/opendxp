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

namespace OpenDxp\Bundle\XliffBundle\ImporterService\Importer;

use OpenDxp\Bundle\XliffBundle\AttributeSet\Attribute;
use OpenDxp\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor\DataObjectDataExtractor;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element;
use Override;

class DataObjectImporter extends AbstractElementImporter
{
    #[Override]
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute): void
    {
        parent::importAttribute($element, $targetLanguage, $attribute);

        if ($attribute->getType() === Attribute::TYPE_LOCALIZED_FIELD) {
            $setter = 'set' . ucfirst($attribute->getName());
            if (method_exists($element, $setter)) {
                $element->$setter($attribute->getContent(), $targetLanguage);
            }
        }

        if ($attribute->getType() === Attribute::TYPE_BRICK_LOCALIZED_FIELD) {
            [$brickField, $brick, $field] = explode(DataObjectDataExtractor::BRICK_DELIMITER, $attribute->getName());

            $brickGetter = null;
            $brickContainerGetter = 'get' . ucfirst($brickField);
            if ($brickContainer = $element->$brickContainerGetter()) {
                $brickGetter = 'get' . ucfirst($brick);
            }

            if (method_exists($brickContainer, $brickGetter)) {
                $brick = $brickContainer->$brickGetter();
                if ($brick instanceof DataObject\Objectbrick\Data\AbstractData) {
                    $localizedFields = $brick->get('localizedfields');
                    if ($localizedFields instanceof DataObject\Localizedfield) {
                        $localizedFields->setLocalizedValue($field, $attribute->getContent(), $targetLanguage);
                    }
                }
            }
        }

        if ($attribute->getType() === Attribute::TYPE_BLOCK_IN_LOCALIZED_FIELD) {
            [$blockName, $blockIndex, $fieldname, $sourceLanguage] = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());
            /** @var array $originalBlockData */
            $originalBlockData = $element->{'get' . $blockName}($sourceLanguage);
            $originalBlockItem = $originalBlockData[$blockIndex] ?? null;
            $originalBlockItemData = $originalBlockItem[$fieldname] ?? null;

            /** @var array $blockData */
            $blockData = $element->{'get' . $blockName}($targetLanguage);
            $blockItem =  $blockData[$blockIndex] ?? $originalBlockItem;
            /** @var DataObject\Data\BlockElement $blockItemData */
            $blockItemData = empty($blockData) ? clone $originalBlockItemData : clone $blockItem[$fieldname];

            $blockItemData->setLanguage($targetLanguage);

            $blockItemData->setData($attribute->getContent());

            $blockItem[$fieldname] = $blockItemData;
            $blockData[$blockIndex] = $blockItem;

            $element->{'set' . $blockName}($blockData, $targetLanguage);
        }

        if ($attribute->getType() === Attribute::TYPE_BLOCK_IN_LOCALIZED_FIELD_COLLECTION) {
            [
                $fieldCollectionName,
                $fieldCollectionItemIndex,
                $blockName,
                $blockIndex,
                $fieldname,
                $sourceLanguage
            ] = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());

            /** @var DataObject\Fieldcollection|null $fieldCollection */
            $fieldCollection = $element->{'get' . $fieldCollectionName}();

            if ($fieldCollection) {
                $item = $fieldCollection->get((int) $fieldCollectionItemIndex);
                if ($item) {
                    /** @var array $originalBlockData */
                    $originalBlockData = $item->{'get' . $blockName}($sourceLanguage);
                    $originalBlockItem = $originalBlockData[$blockIndex] ?? null;
                    $originalBlockItemData = $originalBlockItem[$fieldname] ?? null;

                    /** @var array $blockData */
                    $blockData = $item->{'get' . $blockName}($targetLanguage);
                    $blockItem = $blockData[$blockIndex] ?? $originalBlockItem;

                    /** @var DataObject\Data\BlockElement $blockItemData */
                    $blockItemData = empty($blockData) ? clone $originalBlockItemData : clone $blockItem[$fieldname];

                    $blockItemData->setLanguage($targetLanguage);

                    $blockItemData->setData($attribute->getContent());

                    $blockItem[$fieldname] = $blockItemData;
                    $blockData[$blockIndex] = $blockItem;

                    $item->{'set' . $blockName}($blockData, $targetLanguage);
                }
            }
        }

        if ($attribute->getType() === Attribute::TYPE_BLOCK) {
            [
                $blockName,
                $blockIndex,
                $dummy,
                $fieldname
            ] = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());
            /** @var array $blockData */
            $blockData = $element->{'get' . $blockName}();
            $blockItem = $blockData[$blockIndex];
            $blockItemData = $blockItem['localizedfields'];
            if (!$blockItemData) {
                $blockItemData = new DataObject\Data\BlockElement(
                    'localizedfields',
                    'localizedfields',
                    new DataObject\Localizedfield()
                );
            }
            /** @var DataObject\Localizedfield $localizedFieldData */
            $localizedFieldData = $blockItemData->getData();
            $localizedFieldData->setLocalizedValue($fieldname, $attribute->getContent(), $targetLanguage);
        }

        if ($attribute->getType() === Attribute::TYPE_FIELD_COLLECTION_LOCALIZED_FIELD) {
            [
                $fieldCollectionField,
                $index,
                $field
            ] = explode(DataObjectDataExtractor::FIELD_COLLECTIONS_DELIMITER, $attribute->getName());

            /** @var DataObject\Fieldcollection|null $fieldCollection */
            $fieldCollection = $element->{'get' . $fieldCollectionField}();
            if ($fieldCollection) {
                $item = $fieldCollection->get((int) $index);
                /** @var DataObject\Localizedfield $localizedFields */
                if (
                    $item &&
                    method_exists($item, 'getLocalizedfields') &&
                    ($localizedFields = $item->getLocalizedfields())
                ) {
                    $localizedFields->setLocalizedValue($field, $attribute->getContent(), $targetLanguage);
                }
            }
        }
    }

    #[Override]
    protected function saveElement(Element\ElementInterface $element): void
    {
        if ($element instanceof DataObject\Concrete) {
            $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();

            try {
                DataObject::disableDirtyDetection();
                $element->setOmitMandatoryCheck(true);
                parent::saveElement($element);
            } finally {
                DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
            }
        }
    }
}
