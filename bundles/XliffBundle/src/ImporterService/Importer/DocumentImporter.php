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

namespace OpenDxp\Bundle\XliffBundle\ImporterService\Importer;

use OpenDxp\Bundle\XliffBundle\AttributeSet\Attribute;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use Override;

class DocumentImporter extends AbstractElementImporter
{
    #[Override]
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute): void
    {
        if ($targetLanguage != $element->getProperty('language')) {
            return;
        }

        parent::importAttribute($element, $targetLanguage, $attribute);

        if ($attribute->getType() === Attribute::TYPE_TAG && $element instanceof Document\PageSnippet) {
            $editable = $element->getEditable($attribute->getName());
            if ($editable) {
                if ($editable instanceof Document\Editable\Image || $editable instanceof Document\Editable\Link) {
                    $editable->setText($attribute->getContent());
                } else {
                    $editable->setDataFromEditmode($attribute->getContent());
                }

                $editable->setInherited(false);
                $element->setEditable($editable);
            }
        }

        if (!$element instanceof Document\Page) {
            return;
        }

        if ($attribute->getType() === Attribute::TYPE_ELEMENT_KEY) {
            $element->setKey(Element\Service::getValidKey($attribute->getContent(), 'document'));
        }

        if ($attribute->getType() === Attribute::TYPE_SETTINGS) {
            match ($attribute->getName()) {
                'title' => $element->setTitle($attribute->getContent()),
                'description' => $element->setDescription($attribute->getContent()),
                default => null,
            };
        }
    }
}
