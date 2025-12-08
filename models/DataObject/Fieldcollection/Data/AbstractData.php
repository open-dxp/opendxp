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

namespace OpenDxp\Model\DataObject\Fieldcollection\Data;

use OpenDxp\Model;
use OpenDxp\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\DataObject\ObjectAwareFieldInterface;

/**
 * @method Dao getDao()
 */
abstract class AbstractData extends Model\AbstractModel implements Model\DataObject\LazyLoadedFieldsInterface, Model\Element\ElementDumpStateInterface, Model\Element\DirtyIndicatorInterface, ObjectAwareFieldInterface
{
    use Model\Element\ElementDumpStateTrait;
    use Model\DataObject\Traits\LazyLoadedRelationTrait;
    use Model\Element\Traits\DirtyIndicatorTrait;

    protected int $index = 0;

    protected ?string $fieldname = null;

    protected Concrete|Model\Element\ElementDescriptor|null $object = null;

    protected ?int $objectId = null;

    protected string $type = '';

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
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

    public function getDefinition(): Model\DataObject\Fieldcollection\Definition
    {
        return Model\DataObject\Fieldcollection\Definition::getByKey($this->getType());
    }

    public function setObject(?Concrete $object): static
    {
        $this->objectId = $object?->getId();
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
            if (
                $field instanceof LazyLoadingSupportInterface &&
                $field->getLazyLoading()
            ) {
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

    #[\Override]
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
