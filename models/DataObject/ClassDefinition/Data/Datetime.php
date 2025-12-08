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

use Carbon\Carbon;
use DateTimeInterface;
use OpenDxp\Db;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Normalizer\NormalizerInterface;
use OpenDxp\Tool\UserTimezone;

class Datetime extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Model\DataObject\Traits\DefaultValueTrait;

    /**
     * @internal
     *
     */
    public ?int $defaultValue = null;

    /**
     * @internal
     */
    public bool $useCurrentDate = false;

    /**
     * @internal
     */
    public bool $respectTimezone = true;

    /**
     * @internal
     */
    public string $columnType = 'bigint(20)';

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): int|string|null
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        if ($data) {
            $result = $data->getTimestamp();
            if ($this->getColumnType() === 'datetime') {
                return date('Y-m-d H:i:s', $result);
            }

            return $result;
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?Carbon
    {
        if ($data) {
            if ($this->getColumnType() === 'datetime') {
                $data = strtotime($data);
                if ($data === false) {
                    return null;
                }
            }

            return $this->getDateFromTimestamp($data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): int|string|null
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?int
    {
        if ($data) {
            return $data->getTimestamp();
        }

        return null;
    }

    private function getDateFromTimestamp(float|int|string $timestamp): Carbon
    {
        $date = new Carbon();
        $date->setTimestamp($timestamp);

        return $date;
    }

    /**
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?Carbon
    {
        if (is_numeric($data)) {
            return $this->getDateFromTimestamp($data / 1000);
        }

        if (is_string($data)) {
            return Carbon::parse($data);
        }

        return null;
    }

    public function getDataFromGridEditor(float|string $data, ?Concrete $object = null, array $params = []): Carbon|null
    {
        if ($data && is_float($data)) {
            $data *= 1000;
        }

        return $this->getDataFromEditmode($data, $object, $params);
    }

    public function getDataForGrid(?\DateTime $data, ?Concrete $object = null, array $params = []): ?int
    {
        if ($data) {
            return $data->getTimestamp();
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    #[\Override]
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof DateTimeInterface) {
            return $this->applyTimezone($data)->format('Y-m-d H:i:s');
        }

        return '';
    }

    #[\Override]
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DateTimeInterface) {
            return $this->applyTimezone($data)->format('Y-m-d H:i');
        }

        return '';
    }

    #[\Override]
    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function getDefaultValue(): ?int
    {
        return $this->defaultValue;
    }

    /**
     * @return $this
     */
    public function setDefaultValue(mixed $defaultValue): static
    {
        if ((string)$defaultValue !== '') {
            $this->defaultValue = is_numeric($defaultValue) ? (int)$defaultValue : strtotime($defaultValue);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setUseCurrentDate(bool $useCurrentDate): static
    {
        $this->useCurrentDate = $useCurrentDate;

        return $this;
    }

    public function isUseCurrentDate(): bool
    {
        return $this->useCurrentDate;
    }

    public function isRespectTimezone(): bool
    {
        return $this->respectTimezone;
    }

    public function setRespectTimezone(bool $respectTimezone): static
    {
        $this->respectTimezone = $respectTimezone;

        return $this;
    }

    #[\Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** See parent class.
     *
     *
     */
    #[\Override]
    public function getDiffDataFromEditmode(array $data, ?DataObject\Concrete $object = null, array $params = []): ?Carbon
    {
        $thedata = $data[0]['data'];
        if ($thedata) {
            return $this->getDateFromTimestamp($thedata);
        }

        return null;
    }

    /** See parent class.
     *
     */
    #[\Override]
    public function getDiffDataForEditMode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        $result = [];

        $thedata = null;
        if ($data) {
            $thedata = $data->getTimestamp();
        }
        $diffdata = [];
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->getFieldType();
        $diffdata['value'] = $this->getVersionPreview($data, $object, $params);
        $diffdata['data'] = $thedata;
        $diffdata['title'] = empty($this->title) ? $this->name : $this->title;
        $diffdata['disabled'] = false;

        $result[] = $diffdata;

        return $result;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param array $params optional params used to change the behavior
     *
     */
    #[\Override]
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $timestamp = $value;

        if ($this->getColumnType() === 'datetime') {
            $value = date('Y-m-d', $value);
        }

        if ($operator === '=') {
            $db = Db::get();

            if ($this->getColumnType() === 'datetime') {
                $brickPrefix = $params['brickPrefix'] ? $db->quoteIdentifier($params['brickPrefix']) . '.' : '';

                return 'DATE(' . $brickPrefix . '`' . $params['name'] . '`) = ' . $db->quote($value);
            }
            $maxTime = $timestamp + (86400 - 1);
            //specifies the top point of the range used in the condition
            $filterField = $params['name'] ?: $this->getName();
            return '`' . $filterField . '` BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxTime);
        }

        return parent::getFilterConditionExt($value, $operator, $params);
    }

    #[\Override]
    public function isFilterable(): bool
    {
        return true;
    }

    protected function doGetDefaultValue(Concrete $object, array $context = []): ?Carbon
    {
        if ($this->getDefaultValue()) {
            $date = new \Carbon\Carbon();
            $date->setTimestamp($this->getDefaultValue());
            return $date;
        }
        if ($this->isUseCurrentDate()) {
            return new \Carbon\Carbon();
        }

        return null;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof DateTimeInterface ? $oldValue->format('Y-m-d H:i:s') : null;
        $newValue = $newValue instanceof DateTimeInterface ? $newValue->format('Y-m-d H:i:s') : null;

        return $oldValue === $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Carbon::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Carbon::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Carbon::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Carbon::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?int
    {
        if ($value instanceof Carbon) {
            return $value->getTimestamp();
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?Carbon
    {
        if ($value !== null) {
            return $this->getDateFromTimestamp($value);
        }

        return null;
    }

    /**
     * overwrite default implementation to consider columnType & queryColumnType from class config
     *
     */
    public function resolveBlockedVars(): array
    {
        $defaultBlockedVars = [
            'fieldDefinitionsCache',
        ];

        return [...$defaultBlockedVars, ...$this->getBlockedVarsForExport()];
    }

    public function getColumnType(): string
    {
        return $this->columnType;
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'datetime';
    }

    public function setColumnType(string $columnType): void
    {
        $this->columnType = $columnType;
    }

    private function applyTimezone(DateTimeInterface $date): DateTimeInterface
    {
        if ($this->isRespectTimezone()) {
            return UserTimezone::applyTimezone($date);
        }

        return $date;
    }
}
