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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model;

use Doctrine\DBAL\Exception\DeadlockException;
use Exception;
use OpenDxp;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\FrontendEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Logger;
use OpenDxp\Model\Document\Hardlink\Wrapper\WrapperInterface;
use OpenDxp\Model\Document\Listing;
use OpenDxp\Model\Element\DuplicateFullPathException;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Tool;
use OpenDxp\Tool\Frontend as FrontendTool;
use Override;
use ReflectionClass;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \OpenDxp\Model\Document\Dao getDao()
 * @method bool __isBasedOnLatestData()
 * @method int getChildAmount($user = null)
 * @method string getCurrentFullPath()
 */
class Document extends Element\AbstractElement
{
    private static bool $hideUnpublished = false;

    /**
     * @internal
     */
    protected ?string $fullPathCache = null;

    /**
     * @internal
     */
    protected string $type = '';

    /**
     * @internal
     */
    protected ?string $key = null;

    /**
     * @internal
     */
    protected ?int $index = null;

    /**
     * @internal
     */
    protected bool $published = true;

    /**
     * @internal
     */
    protected ?int $userModification = null;

    /**
     * @internal
     *
     * @var array<string, Listing>
     */
    protected array $children = [];

    /**
     * @internal
     *
     * @var array<string, Listing>
     */
    protected array $siblings = [];

    #[Override]
    protected function getBlockedVars(): array
    {
        $blockedVars = ['versions', 'scheduledTasks', 'fullPathCache'];

        if (!$this->isInDumpState()) {
            // this is if we want to cache the object
            return [...$blockedVars, 'children', 'properties'];
        }

        return $blockedVars;
    }

    public static function getTypes(): array
    {
        $documentsConfig = \OpenDxp\Config::getSystemConfiguration('documents');

        return  array_keys($documentsConfig['type_definitions']['map']);
    }

    public static function getTypesConfiguration(): array
    {
        $documentsConfig = \OpenDxp\Config::getSystemConfiguration('documents');

        // remove unused class value
        return array_map(function ($item) {
            if (array_key_exists('class', $item)) {
                unset($item['class']);
            }

            return $item;
        }, $documentsConfig['type_definitions']['map']);
    }

    /**
     * @internal
     */
    protected static function getPathCacheKey(string $path): string
    {
        return 'document_path_' . md5($path);
    }

    public static function getByPath(string $path, array $params = []): static|null
    {
        if (!$path) {
            return null;
        }

        $path = Element\Service::correctPath($path);

        $cacheKey = self::getPathCacheKey($path);
        $params = Element\Service::prepareGetByIdParams($params);

        if (!$params['force'] && RuntimeCache::isRegistered($cacheKey)) {
            $document = RuntimeCache::get($cacheKey);
            if ($document && static::typeMatch($document)) {
                return $document;
            }
        }

        try {
            $helperDoc = new Document();
            $helperDoc->getDao()->getByPath($path);
            $doc = static::getById($helperDoc->getId(), $params);
            RuntimeCache::set($cacheKey, $doc);
        } catch (NotFoundException) {
            $doc = null;
        }

        return $doc;
    }

    /**
     * @internal
     */
    protected static function typeMatch(Document $document): bool
    {
        $staticType = static::class;
        if ($staticType === Document::class) {
            return true;
        }

        return $document instanceof $staticType;
    }

    /**
     * Caches the reflection-based abstractness check per class, it is needed
     * on every uncached load to decide between new Document() and new static()
     *
     * @var array<class-string, bool>
     */
    private static array $isAbstractCache = [];

    public static function getById(int $id, array $params = []): ?static
    {
        if ($id < 1) {
            return null;
        }

        $cacheKey = self::getCacheKey($id);
        $params = Element\Service::prepareGetByIdParams($params);

        if (!$params['force'] && RuntimeCache::isRegistered($cacheKey)) {
            $document = RuntimeCache::get($cacheKey);
            if ($document && static::typeMatch($document)) {
                return $document;
            }
        }

        if ($params['force'] || !($document = \OpenDxp\Cache::load($cacheKey))) {
            self::$isAbstractCache[static::class] ??= (new ReflectionClass(static::class))->isAbstract();
            $document = self::$isAbstractCache[static::class] ? new Document() : new static();

            try {
                $document->getDao()->getById($id);
            } catch (NotFoundException) {
                return null;
            }

            // Getting classname from document resolver
            $className = OpenDxp::getContainer()->get('opendxp.class.resolver.document')->resolve($document->getType());

            /** @var Document $newDocument */
            $newDocument = self::getModelFactory()->build($className);

            if ($document::class !== $newDocument::class) {
                $document = $newDocument;
                $document->getDao()->getById($id);
            }

            RuntimeCache::set($cacheKey, $document);
            if ($document->getModificationDate() !== null) {
                $document->__setDataVersionTimestamp($document->getModificationDate());
            }

            $document->resetDirtyMap();

            \OpenDxp\Cache::save($document, $cacheKey);
        } else {
            RuntimeCache::set($cacheKey, $document);
        }

        if (!static::typeMatch($document)) {
            return null;
        }

        $dispatcher = OpenDxp::getEventDispatcher();
        if ($dispatcher->hasListeners(DocumentEvents::POST_LOAD)) {
            $dispatcher->dispatch(
                new DocumentEvent($document, ['params' => $params]),
                DocumentEvents::POST_LOAD
            );
        }

        return $document;
    }

    public static function create(int $parentId, array $data = [], bool $save = true): static
    {
        $document = new static();
        $document->setParentId($parentId);
        self::checkCreateData($data);
        $document->setValues($data);

        if ($save) {
            $document->save();
        }

        return $document;
    }

    /**
     * @throws Exception
     */
    public static function getList(array $config = []): Listing
    {
        /** @var Listing $list */
        $list = self::getModelFactory()->build(Listing::class);
        $list->setValues($config);

        return $list;
    }

    public function save(array $parameters = []): static
    {
        $isUpdate = false;

        try {
            $preEvent = new DocumentEvent($this, $parameters);
            if ($this->getId()) {
                $isUpdate = true;
                $this->dispatchEvent($preEvent, DocumentEvents::PRE_UPDATE);
            } else {
                $this->dispatchEvent($preEvent, DocumentEvents::PRE_ADD);
            }

            $parameters = $preEvent->getArguments();

            $this->correctPath();
            $differentOldPath = null;

            // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
            // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
            // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
            $maxRetries = 5;
            for ($retries = 0; $retries < $maxRetries; $retries++) {
                $this->beginTransaction();

                try {
                    $this->updateModificationInfos();

                    if (!$isUpdate) {
                        $this->getDao()->create();
                    }

                    // get the old path from the database before the update is done
                    $oldPath = null;
                    if ($isUpdate) {
                        $oldPath = $this->getDao()->getCurrentFullPath();
                    }

                    $this->update($parameters);

                    // if the old path is different from the new path, update all children
                    $updatedChildren = [];
                    if ($oldPath && $oldPath !== $newPath = $this->getRealFullPath()) {
                        $differentOldPath = $oldPath;
                        $this->getDao()->updateWorkspaces();
                        $updatedChildren = array_map(
                            static function (array $doc) use ($oldPath, $newPath): array {
                                $doc['oldPath'] = substr_replace($doc['path'], $oldPath, 0, strlen($newPath));

                                return $doc;
                            },
                            $this->getDao()->updateChildPaths($oldPath),
                        );
                    }

                    $this->commit();

                    break; // transaction was successfully completed, so we cancel the loop here -> no restart required
                } catch (Exception $e) {
                    try {
                        $this->rollBack();
                    } catch (Exception $er) {
                        // PDO adapter throws exceptions if rollback fails
                        Logger::error((string) $er);
                    }

                    // we try to start the transaction $maxRetries times again (deadlocks, ...)
                    if ($e instanceof DeadlockException && $retries < ($maxRetries - 1)) {
                        $run = $retries + 1;
                        $waitTime = random_int(1, 5) * 100000; // microseconds
                        Logger::warn('Unable to finish transaction (' . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')');

                        usleep($waitTime); // wait specified time until we restart the transaction
                    } else {
                        // if the transaction still fail after $maxRetries retries, we throw out the exception
                        throw $e;
                    }
                }
            }

            $additionalTags = [];
            if (isset($updatedChildren)) {
                foreach ($updatedChildren as $updatedDocument) {
                    $tag = self::getCacheKey($updatedDocument['id']);
                    $additionalTags[] = $tag;

                    // remove the child also from registry (internal cache) to avoid path inconsistencies during long-running scripts, such as CLI
                    RuntimeCache::set($tag, null);
                    RuntimeCache::set(self::getPathCacheKey($updatedDocument['oldPath']), null);
                }
            }
            $this->clearDependentCache($additionalTags);

            if ($differentOldPath) {
                $this->renewInheritedProperties();
            }

            // add to queue that saves dependencies
            $this->addToDependenciesQueue();

            $postEvent = new DocumentEvent($this, $parameters);
            if ($isUpdate) {
                if ($differentOldPath) {
                    $postEvent->setArgument('oldPath', $differentOldPath);
                }
                $this->dispatchEvent($postEvent, DocumentEvents::POST_UPDATE);
            } else {
                $this->dispatchEvent($postEvent, DocumentEvents::POST_ADD);
            }

            return $this;
        } catch (Exception $e) {
            $failureEvent = new DocumentEvent($this, $parameters);
            $failureEvent->setArgument('exception', $e);
            if ($isUpdate) {
                $this->dispatchEvent($failureEvent, DocumentEvents::POST_UPDATE_FAILURE);
            } else {
                $this->dispatchEvent($failureEvent, DocumentEvents::POST_ADD_FAILURE);
            }

            throw $e;
        }
    }

    /**
     * @throws Exception|DuplicateFullPathException
     */
    private function correctPath(): void
    {
        // set path
        if ($this->getId() != 1) { // not for the root node
            // check for a valid key, home has no key, so omit the check
            if (!Element\Service::isValidKey($this->getKey(), 'document')) {
                throw new Exception('invalid key for document with id [ ' . $this->getId() . ' ] key is: [' . $this->getKey() . ']');
            }

            if (!$this->getParentId()) {
                throw new Exception('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
            }

            if ($this->getParentId() == $this->getId()) {
                throw new Exception("ParentID and ID are identical, an element can't be the parent of itself in the tree.");
            }

            $parent = Document::getById($this->getParentId());
            if (!$parent) {
                throw new Exception('ParentID not found.');
            }

            // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
            // that is currently in the parent object (in memory), because this might have changed but wasn't not saved
            $this->setPath(str_replace('//', '/', $parent->getCurrentFullPath() . '/'));

            if (strlen($this->getKey()) < 1) {
                throw new Exception('Document requires key, generated key automatically');
            }
        } elseif ($this->getId() == 1) {
            // some data in root node should always be the same
            $this->setParentId(0);
            $this->setPath('/');
            $this->setKey('');
            $this->setType('page');
        }

        if (Document\Service::pathExists($this->getRealFullPath())) {
            $duplicate = Document::getByPath($this->getRealFullPath());
            if ($duplicate instanceof Document && $duplicate->getId() !== $this->getId()) {
                $duplicateFullPathException = new DuplicateFullPathException('Duplicate full path [ ' . $this->getRealFullPath() . ' ] - cannot save document');
                $duplicateFullPathException->setDuplicateElement($duplicate);
                $duplicateFullPathException->setCauseElement($this);

                throw $duplicateFullPathException;
            }
        }

        $this->validatePathLength();
    }

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws Exception
     *
     * @internal
     */
    protected function update(array $params = []): void
    {
        $disallowedKeysInFirstLevel = ['install', 'admin', 'plugin'];
        if ($this->getParentId() == 1 && in_array($this->getKey(), $disallowedKeysInFirstLevel)) {
            throw new Exception('Key: ' . $this->getKey() . ' is not allowed in first level (root-level)');
        }

        // set index if null
        if ($this->getIndex() === null) {
            $this->setIndex($this->getDao()->getNextIndex());
        }

        if ($this->isFieldDirty('properties')) {
            // save properties
            $properties = $this->getProperties();
            $this->getDao()->deleteAllProperties();
            foreach ($properties as $property) {
                if (!$property->getInherited()) {
                    $property->setDao(null);
                    $property->setCid($this->getId());
                    $property->setCtype('document');
                    $property->setCpath($this->getRealFullPath());
                    $property->save();
                }
            }
        }

        $this->getDao()->update();

        //set document to registry
        RuntimeCache::set(self::getCacheKey($this->getId()), $this);
    }

    /**
     * @internal
     */
    public function saveIndex(int $index): void
    {
        $this->getDao()->saveIndex($index);
        $this->clearDependentCache();
    }

    public function clearDependentCache(array $additionalTags = []): void
    {
        try {
            $tags = [$this->getCacheTag(), 'document_properties', 'output'];
            $tags = [...$tags, ...$additionalTags];

            \OpenDxp\Cache::clearTags($tags);
        } catch (Exception $e) {
            Logger::crit((string) $e);
        }
    }

    /**
     * set the children of the document
     *
     *
     * @return $this
     */
    public function setChildren(?Listing $children, bool $includingUnpublished = false): static
    {
        if (!$children instanceof \OpenDxp\Model\Document\Listing) {
            // unset all cached children
            $this->children = [];
        } else {
            $cacheKey = $this->getListingCacheKey([$includingUnpublished]);
            $this->children[$cacheKey] = $children;
        }

        return $this;
    }

    /**
     * Get a list of the children (not recursivly)
     */
    public function getChildren(bool $includingUnpublished = false): Listing
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->children[$cacheKey])) {
            if ($this->getId()) {
                $list = new Document\Listing();
                $list->setUnpublished($includingUnpublished);
                $list->setCondition('parentId = ?', $this->getId());
                $list->setOrderKey('index');
                $list->setOrder('asc');
                $this->children[$cacheKey] = $list;
            } else {
                $list = new Document\Listing();
                $list->setDocuments([]);
                $this->children[$cacheKey] = $list;
            }
        }

        return $this->children[$cacheKey];
    }

    /**
     * Returns true if the document has at least one child
     */
    public function hasChildren(?bool $includingUnpublished = null): bool
    {
        return $this->getDao()->hasChildren($includingUnpublished);
    }

    /**
     * Get a list of the sibling documents
     */
    public function getSiblings(bool $includingUnpublished = false): Listing
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->siblings[$cacheKey])) {
            if ($this->getParentId()) {
                $list = new Document\Listing();
                $list->setUnpublished($includingUnpublished);
                $list->addConditionParam('parentId = ?', $this->getParentId());
                if ($this->getId()) {
                    $list->addConditionParam('id != ?', $this->getId());
                }
                $list->setOrderKey('index');
                $list->setOrder('asc');
                $this->siblings[$cacheKey] = $list;
            } else {
                $list = new Listing();
                $list->setDocuments([]);
                $this->siblings[$cacheKey] = $list;
            }
        }

        return $this->siblings[$cacheKey];
    }

    /**
     * Returns true if the document has at least one sibling
     */
    public function hasSiblings(?bool $includingUnpublished = null): bool
    {
        return $this->getDao()->hasSiblings($includingUnpublished);
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    protected function doDelete(): void
    {
        // remove children
        if ($this->hasChildren()) {
            // delete also unpublished children
            $unpublishedStatus = self::doHideUnpublished();
            self::setHideUnpublished(false);
            foreach ($this->getChildren(true) as $child) {
                if (!$child instanceof WrapperInterface) {
                    $child->delete();
                }
            }
            self::setHideUnpublished($unpublishedStatus);
        }

        // remove all properties
        $this->getDao()->deleteAllProperties();

        // remove dependencies
        $d = $this->getDependencies();
        $d->cleanAllForElement($this);

        // remove translations
        $service = new Document\Service;
        $service->removeTranslation($this);
    }

    public function delete(): void
    {
        $this->dispatchEvent(new DocumentEvent($this), DocumentEvents::PRE_DELETE);

        $this->beginTransaction();

        try {
            if ($this->getId() == 1) {
                throw new Exception('root-node cannot be deleted');
            }

            $this->doDelete();
            $this->getDao()->delete();

            $this->commit();

            //clear parent data from registry
            $parentCacheKey = self::getCacheKey($this->getParentId());
            if (RuntimeCache::isRegistered($parentCacheKey)) {
                $parent = RuntimeCache::get($parentCacheKey);
                if ($parent instanceof self) {
                    $parent->setChildren(null);
                }
            }
        } catch (Exception $e) {
            $this->rollBack();
            $failureEvent = new DocumentEvent($this);
            $failureEvent->setArgument('exception', $e);
            $this->dispatchEvent($failureEvent, DocumentEvents::POST_DELETE_FAILURE);
            Logger::error((string) $e);

            throw $e;
        }

        // clear cache
        $this->clearDependentCache();

        //clear document from registry
        RuntimeCache::set(self::getCacheKey($this->getId()), null);
        RuntimeCache::set(self::getPathCacheKey($this->getRealFullPath()), null);

        $this->dispatchEvent(new DocumentEvent($this), DocumentEvents::POST_DELETE);
    }

    public function getFullPath(bool $force = false): string
    {
        $link = $force ? null : $this->fullPathCache;

        // check if this document is also the site root, if so return /
        try {
            if (!$link && Tool::isFrontend() && Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site->getRootDocument()->getId() === $this->getId()) {
                    $link = '/';
                }
            }
        } catch (Exception $e) {
            Logger::error((string) $e);
        }

        $requestStack = OpenDxp::getContainer()->get('request_stack');
        $mainRequest = $requestStack->getMainRequest();

        if (!$link && Tool::isFrontend()) {
            $link = $this->resolveCrossSiteFullPath($requestStack->getCurrentRequest(), $mainRequest);
        }

        if (!$link) {
            $link = $this->getPath() . $this->getKey();
        }

        if ($mainRequest) {
            // caching should only be done when main request is available as it is done for performance reasons
            // of the web frontend, without a request object there's no need to cache anything
            $this->fullPathCache = $link;
        }

        return $this->prepareFrontendPath($link);
    }

    /**
     * Resolves the full path for a document that lives outside the current site.
     *
     * Two cases exist:
     *
     * Case 1 — Hardlink rewrite
     *   The main request is rendering a hardlink-wrapped page (WrapperInterface).
     *   Snippets on that page load linked documents via Document::getById(), which
     *   always returns the real (unwrapped) source document — losing the hardlink
     *   context. Without this method, those documents would produce paths pointing
     *   to the source site instead of the current (hardlink) site.
     *
     *   Example tree:
     *     Site A: domain-a.com => /site-a/en/section/
     *     Site B: domain-b.com => /site-b/
     *       Hardlink: /site-b/hl => /site-a/en/section (childrenFromSource)
     *
     *   Rendering domain-b.com/hl/page (WrapperInterface in main request):
     *     - Snippet embeds a link to /site-a/en/section/subpage (by ID)
     *     - Document::getById() returns the real document (no hardlink context)
     *     - This method detects the WrapperInterface in the main request and
     *       rewrites /site-a/en/section/subpage => /hl/subpage
     *
     * Case 2 — Absolute URL fallback
     *   No hardlink context exists. The document belongs to a different site with
     *   its own domain. Returns an absolute URL to that domain.
     *   Falls back to GeneralHostResolver if no site domain is configured.
     */
    private function resolveCrossSiteFullPath(?Request $request, ?Request $mainRequest): ?string
    {
        $differentDomain = false;
        $site = FrontendTool::getSiteForDocument($this);

        if (Tool::isFrontendRequestByAdmin() && $site instanceof Site && $request !== null) {
            $differentDomain = $site->getMainDomain() !== $request->getHost();
        }

        if (!$differentDomain && (!Site::isSiteRequest() || FrontendTool::isDocumentInCurrentSite($this))) {
            return null;
        }

        // Case 1: Rewrite the path into the active hardlink scope of the current site.
        $mainDocument = $mainRequest?->attributes->get(DynamicRouter::CONTENT_KEY);
        if ($mainDocument instanceof WrapperInterface) {
            $hardlink = $mainDocument->getHardLinkSource();
            $hardlinkTarget = $hardlink->getSourceDocument();

            if ($hardlinkTarget !== null) {
                // Strip the current site root from the hardlink path.
                // e.g. /site-b/hl => /hl (after stripping /site-b)
                $hardlinkPath = preg_replace(
                    sprintf('@^%s@', preg_quote(Site::getCurrentSite()->getRootPath(), '@')),
                    '',
                    $hardlink->getRealFullPath()
                );

                // Replace the hardlink-target prefix with the hardlink path.
                // e.g. /site-a/en/section/subpage => /hl/subpage
                $link = preg_replace(
                    sprintf('@^%s@', preg_quote($hardlinkTarget->getRealFullPath(), '@')),
                    $hardlinkPath,
                    $this->getRealFullPath()
                );

                if (str_contains($link, $hardlinkPath)) {
                    return $link;
                }

                // Rewrite did not match: document is not under the hardlink target.
                // However, if the document is under the current site root (edge case with overlapping hardlink configurations),
                // pass through unchanged.
                if (str_contains($this->getRealFullPath(), Site::getCurrentSite()->getRootDocument()->getRealFullPath())) {
                    return null;
                }
            }
        }

        // Case 2.1: Absolute URL using the document's own site domain.
        $scheme = sprintf('%s://', Tool::getRequestScheme($request));

        if ($site instanceof Site && $site->getMainDomain()) {

            if ($site->getRootDocument()->getId() === $this->getId()) {
                return sprintf('%s%s/', $scheme, $site->getMainDomain());
            }

            $pattern = sprintf('@^%s/@', preg_quote($site->getRootPath(), '@'));
            $path = preg_replace($pattern, '/', $this->getRealFullPath());

            return $scheme . $site->getMainDomain() . $path;
        }

        // Case 2.2: Absolute URL via GeneralHostResolver
        if (!$this instanceof WrapperInterface) {
            /** @var GeneralHostResolver $generalHostResolver */
            $generalHostResolver = OpenDxp::getContainer()->get(GeneralHostResolver::class);
            $domain = $generalHostResolver->resolve();

            if (!empty($domain)) {
                return sprintf('%s%s%s', $scheme, $domain, $this->getRealFullPath());
            }
        }

        return null;
    }

    private function prepareFrontendPath(string $path): string
    {
        if (Tool::isFrontend()) {
            $path = OpenDxp\Helper\StringHelper::urlEncodeIgnoreSlash($path);

            $event = new GenericEvent($this, [
                'frontendPath' => $path,
            ]);

            $this->dispatchEvent($event, FrontendEvents::DOCUMENT_PATH);
            $path = $event->getArgument('frontendPath');
        }

        return $path;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    #[Override]
    public function getPath(): ?string
    {
        // check for site, if so rewrite the path for output
        try {
            if ($this->path && Tool::isFrontend() && Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site->getRootDocument() instanceof Document\Page && $site->getRootDocument() !== $this) {
                    $rootPath = $site->getRootPath();
                    $rootPath = preg_quote($rootPath, '@');

                    return preg_replace('@^' . $rootPath . '@', '', $this->path);
                }
            }
        } catch (Exception $e) {
            Logger::error((string) $e);
        }

        return $this->path;
    }

    public function getRealPath(): ?string
    {
        return $this->path;
    }

    public function getRealFullPath(): string
    {
        return $this->getRealPath() . $this->getKey();
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Set the parent id of the document.
     */
    #[Override]
    public function setParentId(?int $id): static
    {
        parent::setParentId($id);

        $this->siblings = [];

        return $this;
    }

    /**
     * Returns the document index.
     */
    public function getIndex(): ?int
    {
        return $this->index;
    }

    /**
     * Set the document index.
     *
     *
     * @return $this
     */
    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the document type.
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->getPublished();
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->markFieldDirty('published');
        $this->published = $published;

        return $this;
    }

    #[Override]
    public function getParent(): ?Document
    {
        $parent = parent::getParent();

        return $parent instanceof Document ? $parent : null;
    }

    /**
     * Set the parent document instance.
     */
    public function setParent(?ElementInterface $parent): static
    {
        /** @var OpenDxp\Model\Element\AbstractElement $parent */
        $this->parent = $parent;
        if ($parent instanceof Document) {
            $this->parentId = $parent->getId();
        }

        return $this;
    }

    /**
     * Set true if want to hide documents.
     */
    public static function setHideUnpublished(bool $hideUnpublished): void
    {
        self::$hideUnpublished = $hideUnpublished;
    }

    /**
     * Checks if unpublished documents should be hidden.
     */
    public static function doHideUnpublished(): bool
    {
        return self::$hideUnpublished;
    }

    /**
     * @internal
     */
    protected function getListingCacheKey(array $args = []): string
    {
        $includingUnpublished = (bool)($args[0] ?? false);

        return 'document_list_' . ($includingUnpublished ? '1' : '0');
    }

    #[Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->parent = null;
        $this->siblings = [];
        $this->fullPathCache = null;
    }
}
