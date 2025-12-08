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

namespace OpenDxp\Model\DataObject\Objectbrick\Data;

use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Exception\InheritanceParentNotFoundException;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\DataObject\ObjectAwareFieldInterface;
use OpenDxp\Model\DataObject\Service;
use Override;

/**
 * @method Dao getDao()
 * @method void save(Concrete $object, array $params = [])
 * @method array getRelationData(string $field, bool $forOwner, ?string $remoteClassId = null)
 */
abstract class AbstractData extends Model\AbstractModel implements Model\DataObject\LazyLoadedFieldsInterface, Model\Element\ElementDumpStateInterface, Model\Element\DirtyIndicatorInterface, ObjectAwareFieldInterface
{
    use Model\DataObject\Traits\LazyLoadedRelationTrait;
    use Model\Element\ElementDumpStateTrait;
    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * Will be overriden by the actual ObjectBrick
     *
     */
    protected string $type = '';

    protected ?string $fieldname = null;

    protected bool $doDelete = false;

    protected Concrete|Model\Element\ElementDescriptor|null $object = null;

    protected ?int $objectId = null;

    public function __construct(Concrete $object)
    {
        $this->setObject($object);
    }

    public function getFieldname(): ?string
    {
        return $this->fieldname;
    }

    public function setFieldname(?string $fieldname): static
    {
        $this->fieldname = $fieldname;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefinition(): DataObject\Objectbrick\Definition
    {
        return DataObject\Objectbrick\Definition::getByKey($this->getType());
    }

    public function setDoDelete(bool $doDelete): static
    {
        $this->flushContainer();
        $this->doDelete = $doDelete;

        return $this;
    }

    public function getDoDelete(): bool
    {
        return $this->doDelete;
    }

    public function getBaseObject(): ?Concrete
    {
        return $this->getObject();
    }

    public function delete(Concrete $object): void
    {
        $this->doDelete = true;
        $this->getDao()->delete($object);
        $this->flushContainer();
    }

    /**
     * @internal
     * Flushes the already collected items of the container object
     */
    protected function flushContainer(): void
    {
        $object = $this->getObject();
        if ($object) {
            $containerGetter = 'get' . ucfirst($this->fieldname);

            $container = $object->$containerGetter();
            if ($container instanceof DataObject\Objectbrick) {
                $container->setItems([]);
            }
        }
    }

    /**
     *
     *
     * @throws InheritanceParentNotFoundException
     */
    public function getValueFromParent(string $key): mixed
    {
        $object = $this->getObject();
        if ($object) {
            $parent = DataObject\Service::hasInheritableParentObject($object);

            if (!empty($parent)) {
                $containerGetter = 'get' . ucfirst($this->fieldname);
                $brickGetter = 'get' . ucfirst($this->getType());
                $getter = 'get' . ucfirst($key);

                if ($parent->$containerGetter()->$brickGetter()) {
                    return $parent->$containerGetter()->$brickGetter()->$getter();
                }
            }
        }

        throw new InheritanceParentNotFoundException('No parent object available to get a value from');
    }

    public function setObject(?Concrete $object): static
    {
        $this->objectId = $object ? $object->getId() : null;
        $this->object = $object;

        if (property_exists($this, 'localizedfields') && $this->localizedfields instanceof Localizedfield) {
            $this->localizedfields->setObjectOmitDirty($object);
        }

        return $this;
    }

    public function getObject(): ?Concrete
    {
        return $this->object;
    }

    public function getValueForFieldName(string $key): mixed
    {
        if ($this->$key) {
            return $this->$key;
        }

        $definition = $this->getDefinition();
        $fd = $definition->getFieldDefinition($key);
        if ($fd instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
            $value = new Model\DataObject\Data\CalculatedValue($key);
            $value->setContextualData('objectbrick', $this->getFieldname(), $definition->getKey(), $fd->getName(), null, null, $fd);

            return Service::getCalculatedFieldValue($this, $value);
        }

        return null;
    }

    public function get(string $fieldName, ?string $language = null): mixed
    {
        return $this->{'get'.ucfirst($fieldName)}($language);
    }

    public function set(string $fieldName, mixed $value, ?string $language = null): mixed
    {
        return $this->{'set'.ucfirst($fieldName)}($value, $language);
    }

    /**
     * @internal
     *
     */
    protected function getLazyLoadedFieldNames(): array
    {
        $lazyLoadedFieldNames = [];
        $fields = $this->getDefinition()->getFieldDefinitions(['suppressEnrichment' => true]);
        foreach ($fields as $field) {
            if ($field instanceof LazyLoadingSupportInterface && $field->getLazyLoading()) {
                $lazyLoadedFieldNames[] = $field->getName();
            }
        }

        return $lazyLoadedFieldNames;
    }

    public function isAllLazyKeysMarkedAsLoaded(): bool
    {
        $object = $this->getObject();
        if ($object instanceof Concrete) {
            return $this->getObject()->isAllLazyKeysMarkedAsLoaded();
        }

        return true;
    }

    #[Override]
    public function __sleep(): array
    {
        $parentVars = parent::__sleep();
        $blockedVars = ['loadedLazyKeys', 'object'];
        $finalVars = [];

        if (!$this->isInDumpState()) {
            //Remove all lazy loaded fields if item gets serialized for the cache (not for versions)
            $blockedVars = [...$this->getLazyLoadedFieldNames(), ...$blockedVars];
        }

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function __wakeup(): void
    {
        if ($this->object) {
            $this->objectId = $this->object->getId();
        }
    }
}
