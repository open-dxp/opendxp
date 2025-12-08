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

namespace OpenDxp\Model\DataObject\ClassDefinition\Data;

use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Normalizer\NormalizerInterface;
use Override;

class Slider extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     *
     */
    public ?float $minValue = null;

    /**
     * @internal
     *
     */
    public ?float $maxValue = null;

    /**
     * @internal
     */
    public bool $vertical = false;

    /**
     * @internal
     *
     */
    public ?float $increment = null;

    /**
     * @internal
     *
     */
    public ?int $decimalPrecision = null;

    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    /**
     * @return $this
     */
    public function setMinValue(?float $minValue): static
    {
        $this->minValue = $minValue;

        return $this;
    }

    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    /**
     * @return $this
     */
    public function setMaxValue(?float $maxValue): static
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    public function getVertical(): bool
    {
        return $this->vertical;
    }

    /**
     * @return $this
     */
    public function setVertical(bool $vertical): static
    {
        $this->vertical = $vertical;

        return $this;
    }

    public function getIncrement(): ?float
    {
        return $this->increment;
    }

    /**
     * @return $this
     */
    public function setIncrement(?float $increment): static
    {
        $this->increment = $increment;

        return $this;
    }

    public function getDecimalPrecision(): ?int
    {
        return $this->decimalPrecision;
    }

    /**
     * @return $this
     */
    public function setDecimalPrecision(?int $decimalPrecision): static
    {
        $this->decimalPrecision = $decimalPrecision;

        return $this;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?float
    {
        if ($data != null) {
            return (float) $data;
        }

        return $data;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?float
    {
        if ($data != null) {
            return (float) $data;
        }

        return $data;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $data;
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    public function getDataFromGridEditor(mixed $data, ?Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    #[Override]
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        return (string)$data;
    }

    #[Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ] '.$data);
        }

        if (!empty($data) && !is_numeric($data)) {
            throw new Model\Element\ValidationException('invalid slider data');
        }
    }

    #[Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Slider $mainDefinition
     */
    #[Override]
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->minValue = $mainDefinition->minValue;
        $this->maxValue = $mainDefinition->maxValue;
        $this->vertical = $mainDefinition->vertical;
        $this->increment = $mainDefinition->increment;
        $this->decimalPrecision = $mainDefinition->decimalPrecision;
    }

    #[Override]
    public function isFilterable(): bool
    {
        return true;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = (float) $oldValue;
        $newValue = (float) $newValue;

        return abs($oldValue - $newValue) < 0.00001;
    }

    #[Override]
    public function isEmpty(mixed $data): bool
    {
        return !is_numeric($data);
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?float';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?float';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'float|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'float|null';
    }

    public function getColumnType(): string
    {
        return 'double';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'slider';
    }
}
