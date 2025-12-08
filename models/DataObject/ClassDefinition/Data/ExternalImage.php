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

class ExternalImage extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    /**
     * @internal
     *
     */
    public ?int $previewWidth = null;

    /**
     * @internal
     *
     */
    public ?int $inputWidth = null;

    /**
     * @internal
     *
     */
    public ?int $previewHeight = null;

    public function getPreviewWidth(): ?int
    {
        return $this->previewWidth;
    }

    public function setPreviewWidth(?int $previewWidth): void
    {
        $this->previewWidth = $previewWidth;
    }

    public function getPreviewHeight(): ?int
    {
        return $this->previewHeight;
    }

    public function setPreviewHeight(?int $previewHeight): void
    {
        $this->previewHeight = $previewHeight;
    }

    public function getInputWidth(): ?int
    {
        return $this->inputWidth;
    }

    public function setInputWidth(?int $inputWidth): void
    {
        $this->inputWidth = $inputWidth;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\ExternalImage
    {
        $externalImage = new Model\DataObject\Data\ExternalImage($data);

        if (isset($params['owner'])) {
            $externalImage->_setOwner($params['owner']);
            $externalImage->_setOwnerFieldname($params['fieldname']);
            $externalImage->_setOwnerLanguage($params['language'] ?? null);
        }

        return $externalImage;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    public function getDataForGrid(?DataObject\Data\ExternalImage $data, ?Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\ExternalImage
    {
        return new Model\DataObject\Data\ExternalImage($data);
    }

    public function getDataFromGridEditor(?string $data, ?Concrete $object = null, array $params = []): DataObject\Data\ExternalImage
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
        if ($data instanceof Model\DataObject\Data\ExternalImage && $data->getUrl()) {
            return '<img style="max-width:200px;max-height:200px" src="' . $data->getUrl()  . '" /><br><a href="' . $data->getUrl() . '">' . $data->getUrl() . '</>';
        }

        return '';
    }

    #[Override]
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            $return = $data->getUrl();
        }

        return $return ?? '';
    }

    #[Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL.
     *
     *
     */
    public function getDiffVersionPreview(string $data, ?Concrete $object = null, array $params = []): string
    {
        if ($data) {
            return '<img style="max-width:200px;max-height:200px" src="' . $data  . '" />';
        }

        return $data;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\ExternalImage $mainDefinition
     */
    #[Override]
    public function synchronizeWithMainDefinition(Model\DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->previewHeight = $mainDefinition->previewHeight;
        $this->previewWidth = $mainDefinition->previewWidth;
        $this->inputWidth = $mainDefinition->inputWidth;
    }

    /**
     *
     * @throws Model\Element\ValidationException
     */
    #[Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if ($this->getMandatory() && !$omitMandatoryCheck && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    #[Override]
    public function isEmpty(mixed $data): bool
    {
        return !($data instanceof DataObject\Data\ExternalImage && $data->getUrl());
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\ExternalImage ? $oldValue->getUrl() : null;
        $newValue = $newValue instanceof DataObject\Data\ExternalImage ? $newValue->getUrl() : null;

        return $oldValue === $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ExternalImage::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ExternalImage::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\ExternalImage::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\ExternalImage::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\ExternalImage) {
            return [
                'url' => $value->getUrl(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\ExternalImage
    {
        if (is_array($value)) {
            return new DataObject\Data\ExternalImage($value['url']);
        }

        return null;
    }

    public function getColumnType(): string
    {
        return 'longtext';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'externalImage';
    }
}
