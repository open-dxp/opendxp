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
use OpenDxp\Normalizer\NormalizerInterface;
use OpenDxp\Tool\Serialize;
use Override;

class Video extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface,
    IdRewriterInterface,
    FieldDefinitionEnrichmentInterface,
    LayoutDefinitionEnrichmentInterface
{
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

    public const TYPE_ASSET = 'asset';

    public const TYPE_YOUTUBE = 'youtube';

    public const TYPE_VIMEO = 'vimeo';

    public const TYPE_DAILYMOTION = 'dailymotion';

    /**
     * @internal
     */
    public string $uploadPath = '';

    /**
     * @internal
     *
     */
    public ?array $allowedTypes = null;

    /**
     * @internal
     *
     */
    public array $supportedTypes = [
        self::TYPE_ASSET,
        self::TYPE_YOUTUBE,
        self::TYPE_VIMEO,
        self::TYPE_DAILYMOTION,
    ];

    /**
     * @return $this
     */
    public function setUploadPath(string $uploadPath): static
    {
        $this->uploadPath = $uploadPath;

        return $this;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    /**
     * @return $this
     */
    public function setAllowedTypes(?array $allowedTypes): static
    {
        $this->allowedTypes = $allowedTypes;

        return $this;
    }

    public function getAllowedTypes(): ?array
    {
        return $this->allowedTypes;
    }

    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof DataObject\Data\Video) {
            $data = clone $data;
            $data->_setOwner(null);
            $data->_setOwnerFieldname('');
            $data->_setOwnerLanguage(null);

            if ($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getId());
            }
            if ($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getId());
            }

            return Serialize::serialize($data->getObjectVars());
        }

        return null;
    }

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, ?Concrete $object = null, array $params = []): ?DataObject\Data\Video
    {
        if ($data) {
            $raw = Serialize::unserialize($data);

            if ($raw['type'] === 'asset' && $asset = Asset::getById((int) $raw['data'])) {
                $raw['data'] = $asset;
            }

            if ($raw['poster'] && $poster = Asset::getById((int) $raw['poster'])) {
                $raw['poster'] = $poster;
            }

            if ($raw['data']) {
                $video = new DataObject\Data\Video();
                if (isset($params['owner'])) {
                    $video->_setOwner($params['owner']);
                    $video->_setOwnerFieldname($params['fieldname']);
                    $video->_setOwnerLanguage($params['language'] ?? null);
                }
                $video->setData($raw['data']);
                $video->setType($raw['type']);
                $video->setPoster($raw['poster']);
                $video->setTitle($raw['title'] ?? null);
                $video->setDescription($raw['description'] ?? null);

                return $video;
            }
        }

        return null;
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
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $data = clone $data;
            if ($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getRealFullPath());
            }
            if ($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getRealFullPath());
            }

            return $data->getObjectVars();
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\Video
    {
        $video = null;

        if (isset($data['type']) && $data['type'] === 'asset') {
            $data['data'] = Asset::getByPath($data['data']);
        }

        if (!empty($data['poster'])) {
            $data['poster'] = Asset::getByPath($data['poster']);
        }

        if (!empty($data['data'])) {
            $video = new DataObject\Data\Video();
            $video->setData($data['data']);
            $video->setType($data['type']);
            $video->setPoster($data['poster']);
            $video->setTitle($data['title'] ?? null);
            $video->setDescription($data['description'] ?? null);
        }

        return $video;
    }

    public function getDataFromGridEditor(?array $data, ?Concrete $object = null, array $params = []): ?DataObject\Data\Video
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    public function getDataForGrid(?DataObject\Data\Video $data, ?Concrete $object = null, array $params = []): array
    {
        $id = null;
        if ($data && $data->getData() instanceof Asset) {
            $id = $data->getData()->getId();
        }
        $result = $this->getDataForEditmode($data, $object, $params);
        if ($id) {
            $result['id'] = $id;
        }

        return $result ?? [];
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
        if ($data && $data->getType() == 'asset' && $data->getData() instanceof Asset) {
            return '<img src="/admin/asset/get-video-thumbnail?id=' . $data->getData()->getId() . '&width=100&height=100&aspectratio=true" />';
        }

        return parent::getVersionPreview($data, $object, $params);
    }

    #[Override]
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data) {
            $value = $data->getData();
            if ($value instanceof Asset) {
                $value = $value->getId();
            }

            return $data->getType() . '~' . $value;
        }

        return '';
    }

    #[Override]
    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Video) {
            return $data->getTitle() . ' ' . $data->getDescription();
        }

        return '';
    }

    #[Override]
    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if ($data && $data->getData() instanceof Asset && !array_key_exists($data->getData()->getCacheTag(), $tags)) {
            $tags = $data->getData()->getCacheTags($tags);
        }

        if ($data && $data->getPoster() instanceof Asset && !array_key_exists($data->getPoster()->getCacheTag(), $tags)) {
            return $data->getPoster()->getCacheTags($tags);
        }

        return $tags;
    }

    public function enrichFieldDefinition(array $context = []): static
    {
        if (empty($this->getAllowedTypes()) && (isset($context['object']) || isset($context['containerType']))) {
            $this->setAllowedTypes($this->getSupportedTypes());
        }

        return $this;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        return $this->enrichFieldDefinition($context);
    }

    #[Override]
    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data && $data->getData() instanceof Asset) {
            $dependencies['asset_' . $data->getData()->getId()] = [
                'id' => $data->getData()->getId(),
                'type' => 'asset',
            ];
        }

        if ($data && $data->getPoster() instanceof Asset) {
            $dependencies['asset_' . $data->getPoster()->getId()] = [
                'id' => $data->getPoster()->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    #[Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return false;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL.
     *
     *
     */
    public function getDiffVersionPreview(?DataObject\Data\Video $data, ?Concrete $object = null, array $params = []): array|string
    {
        $versionPreview = null;

        if ($data && $data->getData() instanceof Asset) {
            $versionPreview = '/admin/asset/get-video-thumbnail?id=' . $data->getData()->getId() . '&width=100&height=100&aspectratio=true';
        }

        if ($versionPreview) {
            return ['src' => $versionPreview, 'type' => 'img'];
        }

        return '';
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);

        if ($data && $data->getData() instanceof Asset && (array_key_exists('asset', $idMapping) && array_key_exists($data->getData()->getId(), $idMapping['asset']))) {
            $data->setData(Asset::getById((int) $idMapping['asset'][$data->getData()->getId()]));
        }

        if ($data && $data->getPoster() instanceof Asset && (array_key_exists('asset', $idMapping) && array_key_exists($data->getPoster()->getId(), $idMapping['asset']))) {
            $data->setPoster(Asset::getById((int) $idMapping['asset'][$data->getPoster()->getId()]));
        }

        return $data;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldData = [];
        $newData = [];

        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\Video
            || !$newValue instanceof DataObject\Data\Video
            || $oldValue->getType() !== $newValue->getType()) {
            return false;
        }

        $oldData['data'] = $oldValue->getData();

        if ($oldData['data'] instanceof Asset\Video) {
            $oldData['data'] = $oldData['data']->getId();
            $oldData['poster'] = $oldValue->getPoster();
            $oldData['title'] = $oldValue->getTitle();
            $oldData['description'] = $oldValue->getDescription();
        }

        $newData['data'] = $newValue->getData();

        if ($newData['data'] instanceof Asset\Video) {
            $newData['data'] = $newData['data']->getId();
            $newData['poster'] = $newValue->getPoster();
            $newData['title'] = $newValue->getTitle();
            $newData['description'] = $newValue->getDescription();
        }

        foreach ($oldData as $key => $oValue) {
            if (!isset($newData[$key]) || $oValue !== $newData[$key]) {
                return false;
            }
        }

        return true;
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\Video) {
            $result = [];
            $result['type'] = $value->getType();
            if ($value->getTitle()) {
                $result['title'] = $value->getTitle();
            }

            if ($value->getDescription()) {
                $result['description'] = $value->getDescription();
            }

            $poster = $value->getPoster();
            if ($poster) {
                $result['poster'] = [
                    'type' => Model\Element\Service::getElementType($poster),
                    'id' => $poster->getId(),
                ];
            }

            $data = $value->getData();

            if ($data && $value->getType() == 'asset') {
                $result['data'] = [
                    'type' => Model\Element\Service::getElementType($data),
                    'id' => $data->getId(),
                ];
            } else {
                $result['data'] = $data;
            }

            return $result;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\Video
    {
        if (is_array($value)) {
            $video = new DataObject\Data\Video();
            $video->setType($value['type']);
            $video->setTitle($value['title'] ?? null);
            $video->setDescription($value['description'] ?? null);

            if ($value['poster'] ?? null) {
                $video->setPoster(Model\Element\Service::getElementById($value['poster']['type'], (int) $value['poster']['id']));
            }

            if ($value['data'] ?? null) {
                if (is_array($value['data'])) {
                    $video->setData(Model\Element\Service::getElementById($value['data']['type'], (int) $value['data']['id']));
                } else {
                    $video->setData($value['data']);
                }
            }

            return $video;
        }

        return null;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Video::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Video::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Video::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Video::class . '|null';
    }

    public function getColumnType(): string
    {
        return 'text';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'video';
    }
}
