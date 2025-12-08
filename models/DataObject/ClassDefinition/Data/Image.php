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
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element;
use OpenDxp\Normalizer\NormalizerInterface;

class Image extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, IdRewriterInterface
{
    use ImageTrait;
    use Data\Extension\RelationFilterConditionParser;

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?int
    {
        if ($data instanceof Asset\Image) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?Asset
    {
        if ((int)$data > 0) {
            return Asset\Image::getById((int) $data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?int
    {
        if ($data instanceof Asset\Image) {
            return $data->getId();
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     */
    public function getDataForEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?array
    {
        if ($data instanceof Asset\Image) {
            return $data->getObjectVars();
        }

        return null;
    }

    public function getDataForGrid(?Asset\Image $data, ?Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?Asset\Image
    {
        if ($data && (int)$data['id'] > 0) {
            return Asset\Image::getById((int) $data['id']);
        }

        return null;
    }

    /**
     *
     * @throws Element\ValidationException
     */
    #[\Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && !$data instanceof Asset\Image) {
            throw new Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }
        if ($data !== null && !$data instanceof Asset\Image) {
            throw new Element\ValidationException('Invalid data in field `'.$this->getName().'`');
        }
    }

    public function getDataFromGridEditor(?array $data, ?Concrete $object = null, array $params = []): Asset\Image|null
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     *
     * @see Data::getVersionPreview
     *
     */
    #[\Override]
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail?id=' . $data->getId() . '&width=100&height=100&aspectratio=true" />';
        }

        return '';
    }

    #[\Override]
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Element\ElementInterface) {
            return $data->getRealFullPath();
        }

        return '';
    }

    #[\Override]
    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    #[\Override]
    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if ($data instanceof Asset\Image && !array_key_exists($data->getCacheTag(), $tags)) {
            return $data->getCacheTags($tags);
        }

        return $tags;
    }

    #[\Override]
    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data instanceof Asset) {
            $dependencies['asset_' . $data->getId()] = [
                'id' => $data->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    #[\Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL.
     *
     *
     */
    public function getDiffVersionPreview(?Asset\Image $data, ?Concrete $object = null, array $params = []): array|string
    {
        $versionPreview = null;
        if ($data instanceof Asset\Image) {
            $versionPreview = '/admin/asset/get-image-thumbnail?id=' . $data->getId() . '&width=150&height=150&aspectratio=true';
        }

        if ($versionPreview) {
            return ['src' => $versionPreview, 'type' => 'img'];
        }
        return '';
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);
        if (!$data instanceof Asset\Image) {
            return $data;
        }
        if (array_key_exists('asset', $idMapping) && array_key_exists($data->getId(), $idMapping['asset'])) {
            return Asset::getById((int) $idMapping['asset'][$data->getId()]);
        }

        return $data;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\Image $mainDefinition
     */
    #[\Override]
    public function synchronizeWithMainDefinition(Model\DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->uploadPath = $mainDefinition->uploadPath;
    }

    #[\Override]
    public function isFilterable(): bool
    {
        return true;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof Asset ? $oldValue->getId() : null;
        $newValue = $newValue instanceof Asset ? $newValue->getId() : null;

        return $oldValue === $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Asset\Image::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Asset\Image::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Asset\Image::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Asset\Image::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof \OpenDxp\Model\Asset\Image) {
            return [
                'type' => 'asset',
                'id' => $value->getId(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?Asset
    {
        if (isset($value['id'])) {
            return Asset\Image::getById((int) $value['id']);
        }

        return null;
    }

    /**
     * Filter by relation feature
     *
     *
     */
    #[\Override]
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $name = $params['name'] ?: $this->name;

        return $this->getRelationFilterCondition($value, $operator, $name);
    }

    public function getColumnType(): string
    {
        return 'int(11)';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'image';
    }
}
