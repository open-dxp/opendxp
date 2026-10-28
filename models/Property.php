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

namespace OpenDxp\Model;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Property\PropertyType;

/**
 * @method \OpenDxp\Model\Property\Dao getDao()
 * @method void save()
 */
final class Property extends AbstractModel
{
    protected ?string $name = null;

    protected mixed $data = null;

    protected ?string $type = null;

    protected ?string $ctype = null;

    protected ?string $cpath = null;

    protected ?int $cid = null;

    protected bool $inheritable = false;

    protected bool $inherited = false;

    /**
     * @internal
     *
     * @return $this
     */
    public function setDataFromEditmode(mixed $data): static
    {
        // IMPORTANT: if you use this method be sure that the type of the property is already set

        $type = PropertyType::tryFrom((string) $this->getType());
        if ($type?->isElementReference() === true) {
            $el = Element\Service::getElementByPath($this->getType(), (string)$data);
            $this->data = null;
            if ($el) {
                $this->data = $el->getId();
            }
        } elseif ($type === PropertyType::Bool) {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        } else {
            // plain text
            $this->data = $data;
        }

        return $this;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function setDataFromResource(mixed $data): static
    {
        // IMPORTANT: if you use this method be sure that the type of the property is already set
        // do not set data for object, asset and document here, this is loaded dynamically when calling $this->getData();
        // "date" is a legacy type that is not part of the PropertyType vocabulary
        $type = PropertyType::tryFrom((string) $this->type);
        if ($this->type === 'date') {
            $this->data = \OpenDxp\Tool\Serialize::unserialize($data);
        } elseif ($type === PropertyType::Bool) {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        } else {
            // plain text
            $this->data = $data;
        }

        return $this;
    }

    public function save(): void
    {
        $this->getDao()->save();

        \OpenDxp\Cache::remove($this->getCtype() . '_properties_' . $this->getCid());
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    /**
     * enum('document','asset','object')
     */
    public function getCtype(): ?string
    {
        return $this->ctype;
    }

    public function getData(): mixed
    {
        // lazy-load data of type asset, document, object
        $type = PropertyType::tryFrom((string) $this->getType());
        if ($type?->isElementReference() === true && !$this->data instanceof ElementInterface && is_numeric($this->data)) {
            return Element\Service::getElementById($this->getType(), (int) $this->data);
        }

        return $this->data;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @see PropertyType
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setCid(int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * enum('document','asset','object')
     *
     *
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return $this
     */
    public function setData(mixed $data): static
    {
        if ($data instanceof ElementInterface) {
            $this->setType(Service::getElementType($data));
            $data = $data->getId();
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @see PropertyType
     *
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCpath(): ?string
    {
        return $this->cpath;
    }

    public function getInherited(): bool
    {
        return $this->inherited;
    }

    /**
     * Alias for getInherited()
     */
    public function isInherited(): bool
    {
        return $this->getInherited();
    }

    /**
     * @return $this
     */
    public function setCpath(?string $cpath): static
    {
        $this->cpath = $cpath;

        return $this;
    }

    /**
     * @return $this
     */
    public function setInherited(bool $inherited): static
    {
        $this->inherited = $inherited;

        return $this;
    }

    public function getInheritable(): bool
    {
        return $this->inheritable;
    }

    /**
     * @return $this
     */
    public function setInheritable(bool $inheritable): static
    {
        $this->inheritable = $inheritable;

        return $this;
    }

    /**
     * @internal
     */
    public function resolveDependencies(): array
    {
        $dependencies = [];

        if ($this->getData() instanceof ElementInterface) {
            $elementType = Element\Service::getElementType($this->getData());
            $key = $elementType . '_' . $this->getData()->getId();
            $dependencies[$key] = [
                'id' => $this->getData()->getId(),
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     *
     * @internal
     */
    public function rewriteIds(array $idMapping): void
    {
        if (!$this->isInherited() && array_key_exists($this->getType(), $idMapping) && ($this->getData() instanceof ElementInterface && array_key_exists((int) $this->getData()->getId(), $idMapping[$this->getType()]))) {
            $this->setData(Element\Service::getElementById($this->getType(), (int) $idMapping[$this->getType()][$this->getData()->getId()]));
        }
    }

    /**
     * @internal
     */
    public function serialize(): array
    {
        return [
          'name' => $this->getName(),
          'type' => $this->getType(),
          'data' => $this->getData(),
        ];
    }
}
