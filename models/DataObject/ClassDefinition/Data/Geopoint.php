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

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Geo\AbstractGeo;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Normalizer\NormalizerInterface;
use Override;

class Geopoint extends AbstractGeo implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        if ($data instanceof DataObject\Data\GeoCoordinates) {
            return [
                $this->getName() . '__longitude' => $data->getLongitude(),
                $this->getName() . '__latitude' => $data->getLatitude(),
            ];
        }

        return [
            $this->getName() . '__longitude' => null,
            $this->getName() . '__latitude' => null,
        ];
    }

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, ?Concrete $object = null, array $params = []): ?DataObject\Data\GeoCoordinates
    {
        if (is_array($data) &&
            $data[$this->getName() . '__longitude'] &&
            $data[$this->getName() . '__latitude']
        ) {
            $geopoint = new DataObject\Data\GeoCoordinates($data[$this->getName() . '__latitude'], $data[$this->getName() . '__longitude']);

            if (isset($params['owner'])) {
                $geopoint->_setOwner($params['owner']);
                $geopoint->_setOwnerFieldname($params['fieldname']);
                $geopoint->_setOwnerLanguage($params['language'] ?? null);
            }

            return $geopoint;
        }

        return null;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data instanceof DataObject\Data\GeoCoordinates) {
            return [
                'longitude' => $data->getLongitude(),
                'latitude' => $data->getLatitude(),
            ];
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\GeoCoordinates
    {
        if (is_array($data) && ($data['longitude'] || $data['latitude'])) {
            return new DataObject\Data\GeoCoordinates($data['latitude'], $data['longitude']);
        }

        return null;
    }

    public function getDataFromGridEditor(?array $data, ?Concrete $object = null, array $params = []): ?DataObject\Data\GeoCoordinates
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
        if ($data instanceof DataObject\Data\GeoCoordinates) {
            return $data->getLatitude().','.$data->getLongitude();
        }

        return '';
    }

    #[Override]
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\GeoCoordinates) {
            return $data->getLatitude() . ',' . $data->getLongitude();
        }

        return '';
    }

    #[Override]
    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    #[Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\GeoCoordinates) {
            return [
                'latitude' => $value->getLatitude(),
                'longitude' => $value->getLongitude(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\GeoCoordinates
    {
        if (is_array($value)) {
            $coordinates = new DataObject\Data\GeoCoordinates($value['latitude'], $value['longitude']);

            $coordinates->_setOwnerFieldname($params['fieldname'] ?? null);
            $coordinates->_setOwner($params['owner'] ?? null);
            $coordinates->_setOwnerLanguage($params['language'] ?? null);

            return $coordinates;
        }

        return null;
    }

    public function getDataForGrid(?DataObject\Data\GeoCoordinates $data, ?Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    #[Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $isEmpty = true;
        if ($data) {
            if (!$data instanceof DataObject\Data\GeoCoordinates) {
                throw new ValidationException('Expected an instance of GeoCoordinates');
            }

            if ($data->getLatitude() !== null && $data->getLongitude() !== null) {
                $isEmpty = false;
            }
        }

        if ($isEmpty && !$omitMandatoryCheck && $this->getMandatory()) {
            throw new ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\GeoCoordinates
            || !$newValue instanceof DataObject\Data\GeoCoordinates) {
            return false;
        }

        return (abs($oldValue->getLongitude() - $newValue->getLongitude()) < 0.000000000001)
            && (abs($oldValue->getLatitude() - $newValue->getLatitude()) < 0.000000000001);
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\GeoCoordinates::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\GeoCoordinates::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\GeoCoordinates::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\GeoCoordinates::class . '|null';
    }

    public function getColumnType(): array
    {
        return [
            'longitude' => 'double',
            'latitude' => 'double',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'geopoint';
    }
}
