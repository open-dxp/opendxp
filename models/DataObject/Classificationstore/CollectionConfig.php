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

namespace OpenDxp\Model\DataObject\Classificationstore;

use Exception;
use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DataObjectClassificationStoreEvents;
use OpenDxp\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Model;

/**
 * @method \OpenDxp\Model\DataObject\Classificationstore\CollectionConfig\Dao getDao()
 */
final class CollectionConfig extends Model\AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    /**
     * Store ID
     *
     */
    protected int $storeId = 1;

    protected string $name;

    /**
     * The collection description.
     *
     */
    protected string $description = '';

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    public static function getById(int $id, ?bool $force = false): ?CollectionConfig
    {
        $cacheKey = self::getCacheKey($id);

        try {
            if (!$force && RuntimeCache::isRegistered($cacheKey)) {
                return RuntimeCache::get($cacheKey);
            }
            if (!$force && $config = Cache::load($cacheKey)) {
                RuntimeCache::set($cacheKey, $config);

                return $config;
            }

            $config = new self();
            $config->getDao()->getById($id);

            RuntimeCache::set($cacheKey, $config);
            Cache::save($config, $cacheKey);

            return $config;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function getByName(string $name, int $storeId = 1, ?bool $force = false): ?CollectionConfig
    {
        $cacheKey = self::getCacheKey($storeId, $name);

        try {
            if (!$force && RuntimeCache::isRegistered($cacheKey)) {
                return RuntimeCache::get($cacheKey);
            }

            if (!$force && $config = Cache::load($cacheKey)) {
                RuntimeCache::set($cacheKey, $config);

                return $config;
            }

            $config = new self();
            $config->setName($name);
            $config->setStoreId($storeId ?: 1);
            $config->getDao()->getByName();

            RuntimeCache::set($cacheKey, $config);
            Cache::save($config, $cacheKey);

            return $config;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    public static function create(): CollectionConfig
    {
        $config = new self();
        $config->save();

        return $config;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the description.
     *
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Deletes the key value collection configuration
     */
    public function delete(): void
    {
        $this->dispatchEvent(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_PRE_DELETE);
        if ($this->getId()) {
            $this->removeCache();
        }

        $this->getDao()->delete();
        $this->dispatchEvent(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_POST_DELETE);
    }

    /**
     * Saves the collection config
     */
    public function save(): void
    {
        $isUpdate = false;

        if ($this->getId()) {
            $this->removeCache();

            $isUpdate = true;
            $this->dispatchEvent(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_PRE_UPDATE);
        } else {
            $this->dispatchEvent(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_PRE_ADD);
        }

        $this->getDao()->save();

        if ($isUpdate) {
            $this->dispatchEvent(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_POST_UPDATE);
        } else {
            $this->dispatchEvent(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_POST_ADD);
        }
    }

    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * Returns all groups belonging to this collection
     *
     * @return CollectionGroupRelation[]
     */
    public function getRelations(): array
    {
        $list = new CollectionGroupRelation\Listing();
        $list->setCondition('colId = ' . $this->id);
        $list = $list->load();

        return $list;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Calculate cache key
     */
    private static function getCacheKey(int $id, ?string $name = null): string
    {
        $cacheKey = 'cs_collectionconfig_' . $id;
        if ($name !== null) {
            $cacheKey .= '_' . md5($name);
        }

        return $cacheKey;
    }

    private function removeCache(): void
    {
        // Remove runtime cache
        RuntimeCache::set(self::getCacheKey($this->getId()), null);
        RuntimeCache::set(self::getCacheKey($this->getStoreId(), $this->getName()), null);

        // Remove persisted cache
        Cache::remove(self::getCacheKey($this->getId()));
        Cache::remove(self::getCacheKey($this->getStoreId(), $this->getName()));
    }
}
