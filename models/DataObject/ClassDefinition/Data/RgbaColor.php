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
use OpenDxp\Tool\Serialize;

class RgbaColor extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface,
    BeforeEncryptionMarshallerInterface,
    AfterDecryptionUnmarshallerInterface
{
    use DataObject\Traits\DataWidthTrait;

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        if ($data instanceof Model\DataObject\Data\RgbaColor) {
            $rgb = sprintf('%02x%02x%02x', $data->getR(), $data->getG(), $data->getB());
            $a = sprintf('%02x', $data->getA());

            return [
                $this->getName() . '__rgb' => $rgb,
                $this->getName() . '__a' => $a,
            ];
        }

        return [
            $this->getName() . '__rgb' => null,
            $this->getName() . '__a' => null,
        ];
    }

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?Model\DataObject\Data\RgbaColor
    {
        if (is_array($data) && isset($data[$this->getName() . '__rgb']) && isset($data[$this->getName() . '__a'])) {
            [$r, $g, $b] = sscanf($data[$this->getName() . '__rgb'], '%02x%02x%02x');
            $a = hexdec($data[$this->getName() . '__a']);
            $data = new Model\DataObject\Data\RgbaColor($r, $g, $b, $a);
        }

        if ($data instanceof Model\DataObject\Data\RgbaColor) {
            if (isset($params['owner'])) {
                $data->_setOwner($params['owner']);
                $data->_setOwnerFieldname($params['fieldname']);
                $data->_setOwnerLanguage($params['language'] ?? null);
            }

            return $data;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof  Model\DataObject\Data\RgbaColor) {
            return sprintf('#%02x%02x%02x%02x', $data->getR(), $data->getG(), $data->getB(), $data->getA());
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?Model\DataObject\Data\RgbaColor
    {
        if ($data) {
            $data = trim($data, '# ');
            [$r, $g, $b, $a] = sscanf($data, '%02x%02x%02x%02x');

            return new Model\DataObject\Data\RgbaColor($r, $g, $b, $a);
        }

        return null;
    }

    public function getDataFromGridEditor(?string $data, ?Concrete $object = null, array $params = []): ?Model\DataObject\Data\RgbaColor
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    #[\Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        parent::checkValidity($data, $omitMandatoryCheck);

        if ($data instanceof Model\DataObject\Data\RgbaColor) {
            $this->checkColorComponent($data->getR());
            $this->checkColorComponent($data->getG());
            $this->checkColorComponent($data->getB());
            $this->checkColorComponent($data->getA());
        }
    }

    /**
     * @throws Model\Element\ValidationException
     */
    private function checkColorComponent(?int $color): void
    {
        if (!is_null($color) && !($color >= 0 && $color <= 255)) {
            throw new Model\Element\ValidationException('Color component out of range');
        }
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\RgbaColor $mainDefinition
     */
    #[\Override]
    public function synchronizeWithMainDefinition(Model\DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->width = $mainDefinition->width;
    }

    #[\Override]
    public function isEmpty(mixed $data): bool
    {
        return $data === null;
    }

    /**
     * display the rgba color field data in the grid
     */
    public function getDataForGrid(?Model\DataObject\Data\RgbaColor $data, ?Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    #[\Override]
    public function getVersionPreview(mixed $data, ?Concrete $object = null, array $params = []): string
    {
        if ($data instanceof  Model\DataObject\Data\RgbaColor) {
            $value = $data->getHex(true, true);

            return '<div style="float: left;"><div style="float: left; margin-right: 5px; background-image: ' . ' url(/bundles/opendxpadmin/img/ext/colorpicker/checkerboard.png);">'
                        . '<div style="background-color: ' . $value . '; width:15px; height:15px;"></div></div>' . $value . '</div>';
        }

        return '';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Model\DataObject\Data\RgbaColor) {
            return [
                'r' => $value->getR(),
                'g' => $value->getG(),
                'b' => $value->getB(),
                'a' => $value->getA(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?Model\DataObject\Data\RgbaColor
    {
        if (is_array($value)) {
            $color = new Model\DataObject\Data\RgbaColor();
            $color->setR($value['r']);
            $color->setG($value['g']);
            $color->setB($value['b']);
            $color->setA($value['a']);

            return $color;
        }

        return null;
    }

    #[\Override]
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return $this->getDataForEditmode($data) ?? '';
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     *
     *
     */
    #[\Override]
    public function getFilterCondition(mixed $value, string $operator, array $params = []): string
    {
        $params['name'] = $this->name;

        return $this->getFilterConditionExt(
            $value,
            $operator,
            $params
        );
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
        $db = \OpenDxp\Db::get();
        $name = $key = $params['name'] ?: $this->name;

        if (!str_starts_with($name, 'cskey_')) {
            $key = 'concat(' . $db->quoteIdentifier($name  . '__rgb') .' ,'
            . $db->quoteIdentifier($name  . '__a') .')';
        }

        if ($value === 'NULL') {
            if ($operator === '=') {
                $operator = 'IS';
            } elseif ($operator === '!=') {
                $operator = 'IS NOT';
            }
        } elseif (!is_array($value) && !is_object($value)) {
            $value = $operator === 'LIKE' ? $db->quote('%' . $value . '%') : $db->quote($value);
        }

        return $key . ' ' . $operator . ' ' . $value . ' ';
    }

    public function marshalBeforeEncryption(mixed $value, ?Concrete $object = null, array $params = []): mixed
    {
        return Serialize::serialize($value);
    }

    public function unmarshalAfterDecryption(mixed $value, ?Concrete $object = null, array $params = []): mixed
    {
        return Serialize::unserialize($value);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof Model\DataObject\Data\RgbaColor ? $oldValue->getHex(true, false) : null;
        $newValue = $newValue instanceof Model\DataObject\Data\RgbaColor ? $newValue->getHex(true, false) : null;

        return $oldValue === $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\RgbaColor::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\RgbaColor::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Model\DataObject\Data\RgbaColor::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Model\DataObject\Data\RgbaColor::class . '|null';
    }

    public function getColumnType(): array
    {
        return [
            'rgb' => 'VARCHAR(6) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL',
            'a' => 'VARCHAR(2) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'rgbaColor';
    }
}
