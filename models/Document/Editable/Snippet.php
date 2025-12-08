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

namespace OpenDxp\Model\Document\Editable;

use OpenDxp;
use OpenDxp\Bundle\PersonalizationBundle\Targeting\Document\DocumentTargetingConfigurator;
use OpenDxp\Cache;
use OpenDxp\Document\Editable\EditableHandler;
use OpenDxp\Model;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use OpenDxp\Tool\DeviceDetector;

/**
 * @method \OpenDxp\Model\Document\Editable\Dao getDao()
 */
class Snippet extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface, LazyLoadingInterface
{
    /**
     * Contains the ID of the linked snippet
     *
     * @internal
     */
    protected ?int $id = null;

    /**
     * Contains the object for the snippet
     *
     * @internal
     */
    protected Document\Snippet|Model\Element\ElementDescriptor|null $snippet = null;

    public function getType(): string
    {
        return 'snippet';
    }

    public function getData(): mixed
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getDataEditmode(): ?array
    {
        if ($this->snippet instanceof Document\Snippet) {
            return [
                'id' => $this->id,
                'path' => $this->snippet->getPath() . $this->snippet->getKey(),
            ];
        }

        return null;
    }

    public function frontend()
    {
        // TODO inject services via DI when editables are built through container
        $container = OpenDxp::getContainer();

        $editableHandler = $container->get(EditableHandler::class);

        if (!$this->snippet instanceof Document\Snippet) {
            return '';
        }

        if (!$this->snippet->isPublished()) {
            return '';
        }

        //Personalization & Targeting Specific
        // @phpstan-ignore-next-line
        if ($container->has(DocumentTargetingConfigurator::class)) {
            $targetingConfigurator = $container->get(DocumentTargetingConfigurator::class);
            // apply best matching target group (if any)
            $targetingConfigurator->configureTargetGroup($this->snippet);
        }

        $params = $this->config;
        $params['document'] = $this->snippet;

        // check if output-cache is enabled, if so, we're also using the cache here
        $cacheKey = null;
        $cacheConfig = \OpenDxp\Tool\Frontend::isOutputCacheEnabled();
        if ((isset($params['cache']) && $params['cache'] === true) || $cacheConfig) {
            // cleanup params to avoid serializing Element\ElementInterface objects
            $cacheParams = $params;
            array_walk($cacheParams, function (&$value, $key): void {
                if ($value instanceof Model\Element\ElementInterface) {
                    $value = $value->getId();
                }
            });

            // TODO is this enough for cache or should we disable caching completely?
            if (method_exists($this->snippet, 'getUseTargetGroup') && $this->snippet->getUseTargetGroup()) {
                $cacheParams['target_group'] = $this->snippet->getUseTargetGroup();
            }

            if (Site::isSiteRequest()) {
                $cacheParams['siteId'] = Site::getCurrentSite()->getId();
            }

            $cacheKey = 'editable_snippet__' . md5(serialize($cacheParams));
            if ($content = Cache::load($cacheKey)) {
                return $content;
            }
        }

        $content = $editableHandler->renderAction($this->snippet->getController(), $params);

        // write contents to the cache, if output-cache is enabled
        if ($cacheConfig && !DeviceDetector::getInstance()->wasUsed()) {
            $cacheTags = ['output_inline'];
            $cacheTags[] = $cacheConfig['lifetime'] ? 'output_lifetime' : 'output';
            Cache::save($content, $cacheKey, $cacheTags, $cacheConfig['lifetime']);
        } elseif (isset($params['cache']) && $params['cache'] === true) {
            Cache::save($content, $cacheKey, ['output']);
        }

        return $content;
    }

    public function setDataFromResource(mixed $data): static
    {
        $data = (int) $data;
        if ($data > 0) {
            $this->id = $data;
            $this->snippet = Document\Snippet::getById($this->id);
        }

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        if ((int)$data > 0) {
            $this->id = $data;
            $this->snippet = Document\Snippet::getById($this->id);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        $this->load();
        return !$this->snippet instanceof Document\Snippet;
    }

    #[\Override]
    public function resolveDependencies(): array
    {
        $dependencies = [];

        if ($this->snippet instanceof Document\Snippet) {
            $key = 'document_' . $this->snippet->getId();

            $dependencies[$key] = [
                'id' => $this->snippet->getId(),
                'type' => 'document',
            ];
        }

        return $dependencies;
    }

    #[\Override]
    public function __sleep(): array
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['snippet'];
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function load(): void
    {
        if (!$this->snippet && $this->id) {
            $this->snippet = Document\Snippet::getById($this->id);
        }
    }

    public function rewriteIds(array $idMapping): void
    {
        $id = $this->getId();
        if (array_key_exists('document', $idMapping) && array_key_exists($id, $idMapping['document'])) {
            $this->id = $idMapping['document'][$id];
        }
    }

    public function setSnippet(Document\Snippet $snippet): void
    {
        $this->id = $snippet->getId();
        $this->snippet = $snippet;
    }

    public function getSnippet(): ?Document\Snippet
    {
        $this->load();

        return $this->snippet;
    }
}
