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
use OpenDxp\Model\Element;
use OpenDxp\Normalizer\NormalizerInterface;
use OpenDxp\Tool\Serialize;

class ImageGallery extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, IdRewriterInterface
{
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     *
     */
    public string $uploadPath;

    /**
     * @internal
     */
    public ?int $ratioX = null;

    /**
     * @internal
     */
    public ?int $ratioY = null;

    /**
     * @internal
     *
     */
    public string $predefinedDataTemplates;

    public function setRatioX(int $ratioX): void
    {
        $this->ratioX = $ratioX;
    }

    public function getRatioX(): int
    {
        return $this->ratioX;
    }

    public function setRatioY(int $ratioY): void
    {
        $this->ratioY = $ratioY;
    }

    public function getRatioY(): int
    {
        return $this->ratioY;
    }

    public function getPredefinedDataTemplates(): string
    {
        return $this->predefinedDataTemplates;
    }

    public function setPredefinedDataTemplates(string $predefinedDataTemplates): void
    {
        $this->predefinedDataTemplates = $predefinedDataTemplates;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    public function setUploadPath(string $uploadPath): void
    {
        $this->uploadPath = $uploadPath;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        if ($data instanceof DataObject\Data\ImageGallery) {
            $hotspots = [];
            $ids = [];
            $fd = new Hotspotimage();

            foreach ($data as $item) {
                $itemData = $fd->getDataForResource($item, $object, $params);
                $ids[] = $itemData['__image'];
                $hotspots[] = $itemData['__hotspots'];
            }

            $elementCount = count($ids);
            $ids = implode(',', $ids);
            if ($elementCount > 0) {
                $ids = ',' . $ids . ',';
            }

            return [
                $this->getName() . '__images' => $ids,
                $this->getName() . '__hotspots' => Serialize::serialize($hotspots),
            ];
        }

        return [
            $this->getName() . '__images' => null,
            $this->getName() . '__hotspots' => null,
        ];
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\ImageGallery
    {
        if (!is_array($data)) {
            return $this->createEmptyImageGallery($params);
        }

        $images = $data[$this->getName() . '__images'];
        $hotspots = $data[$this->getName() . '__hotspots'];
        $hotspots = Serialize::unserialize($hotspots);

        if (!$images) {
            return $this->createEmptyImageGallery($params);
        }

        $resultItems = [];

        $fd = new Hotspotimage();

        $images = array_map(intval(...), explode(',', $images));
        for ($i = 1; $i < count($images) - 1; $i++) {
            $imageId = $images[$i];
            $hotspotData = $hotspots[$i - 1];

            $itemData = [
                $fd->getName() . '__image' => $imageId,
                $fd->getName() . '__hotspots' => $hotspotData,
            ];

            $itemResult = $fd->getDataFromResource($itemData, $object, $params);
            if ($itemResult instanceof DataObject\Data\Hotspotimage) {
                $resultItems[] = $itemResult;
            }
        }

        $imageGallery = new DataObject\Data\ImageGallery($resultItems);

        if (isset($params['owner'])) {
            $imageGallery->_setOwner($params['owner']);
            $imageGallery->_setOwnerFieldname($params['fieldname']);
            $imageGallery->_setOwnerLanguage($params['language'] ?? null);
        }

        return $imageGallery;
    }

    private function createEmptyImageGallery(array $params): DataObject\Data\ImageGallery
    {
        $imageGallery = new DataObject\Data\ImageGallery();

        if (isset($params['owner'])) {
            $imageGallery->_setOwner($params['owner']);
            $imageGallery->_setOwnerFieldname($params['fieldname']);
            $imageGallery->_setOwnerLanguage($params['language'] ?? null);
        }

        return $imageGallery;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        $result = [];
        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $itemData = $fd->getDataForEditmode($item);
                $result[] = $itemData;
            }
        }

        return $result;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\ImageGallery
    {
        $resultItems = [];

        if (is_array($data)) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $resultItem = $fd->getDataFromEditmode($item);
                $resultItems[] = $resultItem;
            }
        }

        return new DataObject\Data\ImageGallery($resultItems);
    }

    public function getDataFromGridEditor(?array $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\ImageGallery
    {
        return $this->getDataFromEditmode($data, $object, $params);
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
        if ($data instanceof DataObject\Data\ImageGallery) {
            return count($data->getItems()) . ' items';
        }

        return '';
    }

    #[\Override]
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\ImageGallery) {
            return base64_encode(Serialize::serialize($data));
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
        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $tags = $fd->getCacheTags($item, $tags);
            }
        }

        return array_unique($tags);
    }

    #[\Override]
    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $itemDependencies = $fd->resolveDependencies($item);
                $dependencies = [...$dependencies, ...$itemDependencies];
            }
        }

        return $dependencies;
    }

    public function getDataForGrid(?DataObject\Data\ImageGallery $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);
        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $fd->doRewriteIds($container, $idMapping, $params, $item);
            }
        }

        return $data;
    }

    /**
     *
     * @throws Element\ValidationException
     */
    #[\Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (
            $this->getMandatory() && !$omitMandatoryCheck &&
            ($data === null || empty($data->getItems()) || $data->hasValidImages() === false)
        ) {
            throw new Model\Element\ValidationException('[ ' . $this->getName() . ' ] At least 1 image should be uploaded!');
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    #[\Override]
    public function isEmpty(mixed $data): bool
    {
        if (empty($data)) {
            return true;
        }

        if ($data instanceof DataObject\Data\ImageGallery) {
            $items = $data->getItems();
            if ($items === []) {
                return true;
            }
        }

        return false;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\ImageGallery ? $oldValue->getItems() : [];
        $newValue = $newValue instanceof DataObject\Data\ImageGallery ? $newValue->getItems() : [];

        if (count($oldValue) !== count($newValue)) {
            return false;
        }

        $fd = new Hotspotimage();

        foreach (array_keys($oldValue) as $i) {
            if (!$fd->isEqual($oldValue[$i], $newValue[$i])) {
                return false;
            }
        }

        return true;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ImageGallery::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ImageGallery::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\ImageGallery::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\ImageGallery::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Model\DataObject\Data\ImageGallery) {
            $list = [];
            $items = $value->getItems();
            $def = new Hotspotimage();
            foreach ($items as $item) {
                if ($item instanceof DataObject\Data\Hotspotimage) {
                    $list[] = $def->normalize($item, $params);
                }
            }

            return $list;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\ImageGallery
    {
        if (is_array($value)) {
            $items = [];
            $def = new Hotspotimage();
            foreach ($value as $rawValue) {
                $items[] = $def->denormalize($rawValue, $params);
            }

            return new DataObject\Data\ImageGallery($items);
        }

        return null;
    }

    public function getColumnType(): array
    {
        return [
            'images' => 'text',
            'hotspots' => 'longtext',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'imageGallery';
    }
}
