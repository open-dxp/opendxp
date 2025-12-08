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

namespace OpenDxp\Bundle\XliffBundle\AttributeSet;

use OpenDxp\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;

class AttributeSet
{
    private string $sourceLanguage = '';

    /**
     * @var string[]
     */
    private array $targetLanguages = [];

    /**
     * @var Attribute[]
     */
    private array $attributes = [];

    /**
     * DataExtractorResult constructor.
     *
     */
    public function __construct(private TranslationItem $translationItem)
    {
    }

    public function getTranslationItem(): TranslationItem
    {
        return $this->translationItem;
    }

    public function setTranslationItem(TranslationItem $translationItem): AttributeSet
    {
        $this->translationItem = $translationItem;

        return $this;
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function setSourceLanguage(string $sourceLanguage): AttributeSet
    {
        $this->sourceLanguage = $sourceLanguage;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }

    /**
     * @param string[] $targetLanguages
     *
     */
    public function setTargetLanguages(array $targetLanguages): AttributeSet
    {
        $this->targetLanguages = $targetLanguages;

        return $this;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isEmpty(): bool
    {
        if ($this->attributes === []) {
            return true;
        }

        foreach ($this->attributes as $attribute) {
            if (!$attribute->isReadonly()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $targetContent
     *
     */
    public function addAttribute(string $type, string $name, string $content, bool $isReadonly = false, array $targetContent = []): AttributeSet
    {
        $this->attributes[] = new Attribute($type, $name, $content, $isReadonly, $targetContent);

        return $this;
    }
}
