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

use Exception;
use OpenDxp\Helper\ArrayHelper;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use Override;
use Stringable;

/**
 * @method \OpenDxp\Model\DataObject\Data\ObjectMetadata\Dao getDao()
 */
class ObjectMetadata extends Model\AbstractModel implements DataObject\OwnerAwareFieldInterface, Stringable
{
    use DataObject\Traits\OwnerAwareFieldTrait;

    protected ?DataObject\AbstractObject $object = null;

    protected ?int $objectId = null;

    protected array $data = [];

    public function __construct(
        protected ?string $fieldname,
        protected array $columns = [],
        ?DataObject\Concrete $object = null
    ) {
        $this->setObject($object);
    }

    /**
     * @return $this
     */
    public function setObject(?DataObject\Concrete $object): static
    {
        $this->markMeDirty();

        if (!$object) {
            $this->setObjectId(null);

            return $this;
        }

        $this->objectId = $object->getId();

        return $this;
    }

    /**
     * @return mixed|void
     *
     * @throws Exception
     */
    #[Override]
    public function __call(string $method, array $args)
    {
        if (str_starts_with($method, 'get')) {
            $key = substr($method, 3, strlen($method) - 3);

            $idx = ArrayHelper::arraySearchCaseInsensitive($key, $this->columns);
            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];

                return $this->data[$correctedKey] ?? null;
            }

            throw new Exception("Requested data $key not available");
        }

        if (str_starts_with($method, 'set')) {
            $key = substr($method, 3, strlen($method) - 3);
            $idx = ArrayHelper::arraySearchCaseInsensitive($key, $this->columns);

            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];
                $this->data[$correctedKey] = $args[0];
                $this->markMeDirty();
            } else {
                throw new Exception("Requested data $key not available");
            }
        }
    }

    public function save(DataObject\Concrete $object, string $ownertype, string $ownername, string $position, int $index): void
    {
        $this->getDao()->save($object, $ownertype, $ownername, $position, $index);
    }

    public function load(DataObject\Concrete $source, int $destinationId, string $fieldname, string $ownertype, string $ownername, string $position, int $index): ?ObjectMetadata
    {
        $return = $this->getDao()->load($source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index);
        $this->markMeDirty(false);

        return $return;
    }

    /**
     * @return $this
     */
    public function setFieldname(string $fieldname): static
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();

        return $this;
    }

    public function getFieldname(): ?string
    {
        return $this->fieldname;
    }

    public function getObject(): ?DataObject\Concrete
    {
        if ($this->getObjectId()) {
            $object = DataObject\Concrete::getById($this->getObjectId());
            if (!$object) {
                Logger::info('object ' . $this->getObjectId() . ' does not exist anymore');
            }

            return $object;
        }

        return null;
    }

    /**
     * @return $this
     */
    public function setElement(DataObject\Concrete $element): static
    {
        $this->markMeDirty();

        return $this->setObject($element);
    }

    public function getElement(): ?DataObject\Concrete
    {
        return $this->getObject();
    }

    /**
     * @return $this
     */
    public function setColumns(array $columns): static
    {
        $this->columns = $columns;
        $this->markMeDirty();

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    public function __toString(): string
    {
        return $this->getObject()?->__toString() ?? '';
    }

    public function getObjectId(): int
    {
        return (int) $this->objectId;
    }

    public function setObjectId(?int $objectId): void
    {
        $this->objectId = $objectId;
    }

    public function __unserialize(array $data): void
    {
        $this->fieldname = $data["\0*\0fieldname"] ?? null;
        $this->columns = $data["\0*\0columns"] ?? [];

        foreach (get_object_vars($this) as $property => $value) {
            if ($property === 'objectId') {
                $this->$property = (int) ($data["\0*\0".$property] ?? $value);

                continue;
            }

            $this->$property = $data["\0*\0".$property] ?? $value;
        }

        if ($this->object) {
            $this->objectId = $this->object->getId();
        }
    }

    #[Override]
    public function __sleep(): array
    {
        $finalVars = [];
        $blockedVars = ['object'];
        $vars = parent::__sleep();

        foreach ($vars as $value) {
            if (!in_array($value, $blockedVars)) {
                $finalVars[] = $value;
            }
        }

        return $finalVars;
    }
}
