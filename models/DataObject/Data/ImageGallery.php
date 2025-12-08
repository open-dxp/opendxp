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

namespace OpenDxp\Model\DataObject\Data;

use Iterator;
use OpenDxp\Model\DataObject\OwnerAwareFieldInterface;
use OpenDxp\Model\DataObject\Traits\OwnerAwareFieldTrait;

class ImageGallery implements Iterator, OwnerAwareFieldInterface, \Stringable
{
    use OwnerAwareFieldTrait;

    /**
     * @var array<int, Hotspotimage|null>
     */
    protected array $items;

    /**
     * @param array<int, Hotspotimage|null> $items
     */
    public function __construct(array $items = [])
    {
        $this->setItems($items);
        $this->markMeDirty();
    }

    public function current(): Hotspotimage|null|false
    {
        return current($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function key(): int|string|null
    {
        return key($this->items);
    }

    public function valid(): bool
    {
        return $this->current() !== false;
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    /**
     * @return array<int, Hotspotimage|null>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<int, Hotspotimage|null> $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
        $this->rewind();
        $this->markMeDirty();
    }

    public function __toString(): string
    {
        if ($this->items) {
            /** @var array<int, Hotspotimage> $filtered */
            $filtered = array_filter($this->items, fn ($item) => !is_null($item));

            return implode(',', array_map(strval(...), $filtered));
        }

        return '';
    }

    public function hasValidImages(): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item instanceof Hotspotimage) {
                return true;
            }
        }

        return false;
    }
}
