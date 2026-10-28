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

namespace OpenDxp\Model\Element\Data;

use ArrayAccess;
use OpenDxp\Model;
use OpenDxp\Model\Element\ElementType;

/**
 * @internal
 */
class MarkerHotspotItem implements ArrayAccess
{
    public string $name = '';

    public string $type = '';

    public mixed $value = null;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . $key;
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * @param string $offset
     */
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * @param string $offset
     */
    public function offsetGet($offset): mixed
    {
        if ($this->offsetExists($offset)) {
            if ($offset === 'value' && in_array($this->type, ElementType::values(), true) && $this->value) {
                return Model\Element\Service::getElementById($this->type, (int) $this->value);
            }

            return $this->$offset;
        }

        return null;
    }

    /**
     * @param string $offset
     */
    public function offsetSet($offset, mixed $value): void
    {
        if ($this->offsetExists($offset)) {
            if ($value instanceof Model\Element\ElementInterface) {
                $value = $value->getId();
            }

            $this->$offset = $value;
        }
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        if ($this->offsetExists($offset)) {
            $this->$offset = null;
        }
    }
}
