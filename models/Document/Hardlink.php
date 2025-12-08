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

namespace OpenDxp\Model\Document;

use OpenDxp;
use OpenDxp\Model;
use OpenDxp\Model\Document;
use Override;

/**
 * @method \OpenDxp\Model\Document\Hardlink\Dao getDao()
 */
class Hardlink extends Document
{
    use Model\Element\Traits\ScheduledTasksTrait;

    protected string $type = 'hardlink';

    /**
     * @internal
     *
     */
    protected ?int $sourceId = null;

    /**
     * @internal
     *
     */
    protected bool $propertiesFromSource = false;

    /**
     * @internal
     *
     */
    protected bool $childrenFromSource = false;

    public function getSourceDocument(): ?Document
    {
        if ($this->getSourceId()) {
            return Document::getById($this->getSourceId());
        }

        return null;
    }

    #[Override]
    public function resolveDependencies(): array
    {
        $dependencies = parent::resolveDependencies();
        $sourceDocument = $this->getSourceDocument();

        if ($sourceDocument instanceof Document) {
            $key = 'document_' . $sourceDocument->getId();

            $dependencies[$key] = [
                'id' => $sourceDocument->getId(),
                'type' => 'document',
            ];
        }

        return $dependencies;
    }

    #[Override]
    public function getCacheTags(array $tags = []): array
    {
        $tags = parent::getCacheTags($tags);

        if ($this->getSourceDocument() && ($this->getSourceDocument()->getId() !== $this->getId() && !array_key_exists($this->getSourceDocument()->getCacheTag(), $tags))) {
            return $this->getSourceDocument()->getCacheTags($tags);
        }

        return $tags;
    }

    public function setChildrenFromSource(bool $childrenFromSource): static
    {
        $this->childrenFromSource = $childrenFromSource;

        return $this;
    }

    public function getChildrenFromSource(): bool
    {
        return $this->childrenFromSource;
    }

    public function setSourceId(?int $sourceId): static
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setPropertiesFromSource(bool $propertiesFromSource): static
    {
        $this->propertiesFromSource = $propertiesFromSource;

        return $this;
    }

    public function getPropertiesFromSource(): bool
    {
        return $this->propertiesFromSource;
    }

    #[Override]
    public function getProperties(): array
    {
        if ($this->properties === null) {
            $properties = parent::getProperties();

            if ($this->getPropertiesFromSource() && $this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getProperties();
                foreach ($sourceProperties as &$prop) {
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = [...$sourceProperties, ...$properties];
            } elseif ($this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getDao()->getProperties(false, true);
                foreach ($sourceProperties as &$prop) {
                    /**
                     * @var Model\Property $prop
                     */
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = [...$sourceProperties, ...$properties];
            }

            $this->setProperties($properties);
        }

        return $this->properties;
    }

    #[Override]
    public function getChildren(bool $includingUnpublished = false): Listing
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());
        if (!isset($this->children[$cacheKey])) {
            $children = parent::getChildren($includingUnpublished);

            $wrappedSourceChildren = [];
            if ($this->getChildrenFromSource() && $this->getSourceDocument() && !OpenDxp::inAdmin()) {
                $sourceChildren = $this->getSourceDocument()->getChildren($includingUnpublished)->getDocuments();
                foreach ($sourceChildren as $key => $c) {
                    $wrappedChild = Document\Hardlink\Service::wrap($c);
                    $wrappedChild->setHardLinkSource($this);
                    $wrappedChild->setPath(preg_replace('@^' . preg_quote($this->getSourceDocument()->getRealFullPath(), '@') . '@', $this->getRealFullPath(), $c->getRealPath()));
                    $wrappedSourceChildren[$key] = $wrappedChild;
                }
            }

            $children->setData([...$wrappedSourceChildren, ...$children->load()]);

            $this->setChildren($children, $includingUnpublished);
        }

        return $this->children[$cacheKey];
    }

    #[Override]
    public function hasChildren(?bool $includingUnpublished = null): bool
    {
        return count($this->getChildren((bool)$includingUnpublished)) > 0;
    }

    #[Override]
    protected function update(array $params = []): void
    {
        parent::update($params);
        $this->saveScheduledTasks();
    }
}
