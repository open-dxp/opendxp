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

namespace OpenDxp\Bundle\XliffBundle\TranslationItemCollection;

use OpenDxp\Model\Element;
use OpenDxp\Model\Element\ElementInterface;

class TranslationItemCollection
{
    /**
     * @var TranslationItem[]
     */
    private array $items = [];

    public function add(string $type, string $id, ElementInterface $element): TranslationItemCollection
    {
        $this->items[] = new TranslationItem($type, $id, $element);

        return $this;
    }

    public function addItem(TranslationItem $item): TranslationItemCollection
    {
        $this->items[] = $item;

        return $this;
    }

    public function addOpenDxpElement(ElementInterface $element): TranslationItemCollection
    {
        $this->items[] = new TranslationItem(Element\Service::getElementType($element), (string) $element->getId(), $element);

        return $this;
    }

    /**
     * @return TranslationItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        $elementsArray = [];
        foreach ($this->getItems() as $element) {
            $elementsArray[$element->getType()] ??= [];
            $elementsArray[$element->getType()][] = $element->getId();
        }

        return $elementsArray;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
