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

namespace OpenDxp\Cache\Core;

use Closure;
use DateInterval;
use DeepCopy\TypeMatcher\TypeMatcher;
use OpenDxp\Event\CoreCacheEvents;
use OpenDxp\Helper\FileSystemHelper;
use OpenDxp\Model\Document\Hardlink\Wrapper\WrapperInterface;
use OpenDxp\Model\Element\DeepCopy\CarbonPeriodFilter;
use OpenDxp\Model\Element\ElementDumpStateInterface;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Version\SetDumpStateFilter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * Core opendxp cache handler with logic handling deferred save on shutdown (specialized for internal opendxp use). This
 * explicitely does not expose a PSR-6 API but is intended for internal use from OpenDxp\Cache or directly. Actual
 * cache calls are forwarded to a PSR-6 cache implementation though.
 *
 * use OpenDxp\Cache static interface, do not use this handler directly
 *
 * @internal
 */
class CoreCacheHandler implements LoggerAwareInterface, ResetInterface
{
    use LoggerAwareTrait;

    /**
     * Actually write/load to/from cache?
     */
    protected bool $enabled = true;

    /**
     * Is the cache handled in CLI mode?
     */
    protected bool $handleCli = false;

    /**
     * Contains the items which should be written to the cache on shutdown
     *
     * @var CacheQueueItem[]
     */
    protected array $saveQueue = [];

    /**
     * Tags which were already cleared
     */
    protected array $clearedTags = [];

    /**
     * Items having one of the tags in this list are not saved
     */
    protected array $tagsIgnoredOnSave = [];

    /**
     * Items having one of the tags in this list are not cleared when calling clearTags
     */
    protected array $tagsIgnoredOnClear = [];

    /**
     * Items having tags which are in this array are cleared on shutdown. This is especially for the output-cache.
     */
    protected array $tagsClearedOnShutdown = [];

    /**
     * State variable which is set to true after the cache was cleared - prevent new items being
     * written to cache after a clear.
     */
    protected bool $cacheCleared = false;

    /**
     * Tags in this list are shifted to the clearTagsOnShutdown list when scheduled via clearTags. See comment on normalizeClearTags
     * method why this exists.
     */
    protected array $shutdownTags = ['output'];

    /**
     * If set to true items are directly written into the cache, and do not get into the queue
     */
    protected bool $forceImmediateWrite = false;

    /**
     * How many items should stored to the cache within one process
     */
    protected int $maxWriteToCacheItems = 50;

    protected bool $writeInProgress = false;

    /**
     * One-shot buffer filled by prefetch() and consumed by load(); holds the
     * item data for hits and false for known misses
     *
     * @var array<string, mixed>
     */
    protected array $prefetchedItems = [];

    protected Closure $emptyCacheItemClosure;

    public function __construct(
        protected TagAwareAdapterInterface $pool,
        protected WriteLock $writeLock,
        protected EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @internal
     */
    public function setPool(TagAwareAdapterInterface $pool): void
    {
        $this->pool = $pool;
    }

    public function getWriteLock(): WriteLock
    {
        return $this->writeLock;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function enable(): static
    {
        $this->enabled = true;
        $this->dispatchStatusEvent();

        return $this;
    }

    public function disable(): static
    {
        $this->enabled = false;
        $this->dispatchStatusEvent();

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    protected function dispatchStatusEvent(): void
    {
        $this->dispatcher->dispatch(new Event(),
            $this->isEnabled()
                ? CoreCacheEvents::ENABLE
                : CoreCacheEvents::DISABLE
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function getHandleCli(): bool
    {
        return $this->handleCli;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return $this
     */
    public function setHandleCli(bool $handleCli): static
    {
        $this->handleCli = $handleCli;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getForceImmediateWrite(): bool
    {
        return $this->forceImmediateWrite;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return $this
     */
    public function setForceImmediateWrite(bool $forceImmediateWrite): static
    {
        $this->forceImmediateWrite = $forceImmediateWrite;

        return $this;
    }

    public function setMaxWriteToCacheItems(int $maxWriteToCacheItems): static
    {
        $this->maxWriteToCacheItems = $maxWriteToCacheItems;

        return $this;
    }

    /**
     * Load data from cache (retrieves data from cache item)
     */
    public function load(string $key): mixed
    {
        if (!$this->enabled) {
            $this->logger->debug('Not loading object {key} from cache (deactivated)', ['key' => $key]);

            return false;
        }

        if (array_key_exists($key, $this->prefetchedItems)) {
            $data = $this->prefetchedItems[$key];
            unset($this->prefetchedItems[$key]);

            return $data;
        }

        $item = $this->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        return false;
    }

    /**
     * Fetch multiple items with a single backend roundtrip and buffer the
     * results (hits and known misses) for the subsequent load() calls of the
     * same keys, which then don't need a backend roundtrip of their own.
     * Buffered entries are consumed on load() and invalidated on writes,
     * removals, and tag/full clears.
     *
     * @param string[] $keys
     */
    public function prefetch(array $keys): void
    {
        if (!$this->enabled) {
            $this->logger->debug('Not prefetching objects {keys} from cache (deactivated)', ['keys' => $keys]);

            return;
        }

        foreach ($this->pool->getItems($keys) as $key => $item) {
            $this->prefetchedItems[$key] = $item->isHit() ? $item->get() : false;
        }
    }

    /**
     * Drops the given buffered prefetch entries without touching the rest of
     * the buffer, e.g. entries prefetched by an outer, still running batch.
     *
     * @param string[] $keys
     */
    public function invalidatePrefetched(array $keys): void
    {
        foreach ($keys as $key) {
            unset($this->prefetchedItems[$key]);
        }
    }

    /**
     * Drops all buffered prefetch entries. Wired to kernel.reset (via service
     * autoconfiguration) so that long-running processes such as Messenger
     * workers cannot serve entries which were prefetched but never consumed
     * (e.g. because a batch aborted) to later, unrelated work.
     */
    public function reset(): void
    {
        $this->prefetchedItems = [];
    }

    /**
     * Get PSR-6 cache item
     */
    public function getItem(string $key): CacheItem
    {
        $item = $this->pool->getItem($key);
        if ($item->isHit()) {
            $this->logger->debug('Successfully got data for key {key} from cache', ['key' => $key]);
        } else {
            $this->logger->debug('Key {key} doesn\'t exist in cache', ['key' => $key]);
        }

        return $item;
    }

    /**
     * Save data to cache
     */
    public function save(string $key, mixed $data, array $tags = [], DateInterval|int|null $lifetime = null, ?int $priority = 0, bool $force = false): bool
    {
        if ($this->writeInProgress) {
            return false;
        }

        CacheItem::validateKey($key);

        if (!$this->enabled) {
            $this->logger->debug('Not saving object {key} to cache (deactivated)', ['key' => $key]);

            return false;
        }

        if ($this->isCli() && (!$this->handleCli && !$force)) {
            $this->logger->debug(
                'Not saving {key} to cache as process is running in CLI mode (pass force to override or set handleCli to true)',
                ['key' => $key]
            );

            return false;
        }

        if ($force || $this->forceImmediateWrite) {
            $data = $this->prepareCacheData($data);
            if (null === $data) {
                // logging is done in prepare method if item could not be created
                return false;
            }

            // add cache tags to item
            $tags = $this->prepareCacheTags($key, $data, $tags);
            if (null === $tags) {
                return false;
            }

            return $this->storeCacheData($key, $data, $tags, $lifetime, $force);
        }
        $cacheQueueItem = new CacheQueueItem($key, $data, $tags, $lifetime, $priority, $force);

        return $this->addToSaveQueue($cacheQueueItem);
    }

    /**
     * Add item to save queue, respecting maxWriteToCacheItems setting
     */
    protected function addToSaveQueue(CacheQueueItem $item): bool
    {
        $data = $this->prepareCacheData($item->getData());
        if ($data) {
            $this->saveQueue[$item->getKey()] = $item;

            if (count($this->saveQueue) > ($this->maxWriteToCacheItems*3)) {
                $this->cleanupQueue();
            }

            return true;
        }

        return false;
    }

    /**
     * @internal
     */
    public function cleanupQueue(): void
    {
        // order by priority
        uasort($this->saveQueue, fn (CacheQueueItem $a, CacheQueueItem $b) => $b->getPriority() <=> $a->getPriority());

        // remove overrun
        array_splice($this->saveQueue, $this->maxWriteToCacheItems);
    }

    /**
     * Prepare data for cache item and handle items we don't want to save (e.g. hardlinks)
     */
    protected function prepareCacheData(mixed $data): mixed
    {
        // do not cache hardlink-wrappers
        if ($data instanceof WrapperInterface) {
            return null;
        }

        // clean up and prepare models
        // check for corrupt data
        if ($data instanceof ElementInterface && !$data->getId()) {
            return null;
        }

        return $data;
    }

    /**
     * Create tags for cache item - do this as late as possible as this is potentially expensive (nested items, dependencies)
     *
     * @param string[] $tags
     *
     * @return null|string[]
     */
    protected function prepareCacheTags(string $key, mixed $data, array $tags = []): ?array
    {
        // clean up and prepare models
        if ($data instanceof ElementInterface) {
            // get tags for this element
            $tags = $data->getCacheTags($tags);

            $this->logger->debug(
                'Prepared {class} {id} for data cache',
                [
                    'class' => $data::class,
                    'id' => $data->getId(),
                    'tags' => $tags,
                ]
            );
        }

        // array_values() because the tags from \Element_Interface and some others are associative eg. array("object_123" => "object_123")
        $tags = array_values($tags);
        $tags = array_unique($tags);

        // check if any of our tags is in cleared tags or tags ignored on save lists
        $tagsIgnoredOnSave = array_flip($this->tagsIgnoredOnSave);
        foreach ($tags as $tag) {
            if (isset($this->clearedTags[$tag])) {
                $this->logger->debug('Aborted caching for key {key} because tag {tag} is in the cleared tags list', [
                    'key' => $key,
                    'tag' => $tag,
                ]);

                return null;
            }

            if (isset($tagsIgnoredOnSave[$tag])) {
                $this->logger->debug('Aborted caching for key {key} because tag {tag} is in the ignored tags on save list', [
                    'key' => $key,
                    'tag' => $tag,
                    'tags' => $tags,
                    'tagsIgnoredOnSave' => $this->tagsIgnoredOnSave,
                ]);

                return null;
            }
        }

        return $tags;
    }

    protected function storeCacheData(string $key, mixed $data, array $tags = [], DateInterval|int|null $lifetime = null, bool $force = false): bool
    {
        if ($this->writeInProgress) {
            return false;
        }

        if (!$this->enabled) {
            // TODO return true here as the noop (not storing anything) is basically successful?
            return false;
        }

        // don't put anything into the cache, when cache is cleared
        if ($this->cacheCleared && !$force) {
            return false;
        }

        unset($this->prefetchedItems[$key]);

        $this->writeInProgress = true;

        if ($data instanceof ElementInterface) {
            // fetch a fresh copy
            $type = Service::getElementType($data);
            $id = $data->getId();

            $data = Service::getElementById($type, $id, ['force' => true]);

            if ($data === null || !$data->__isBasedOnLatestData()) {
                $reason = $data === null
                    ? 'data is null'
                    : 'element is not based on latest data';

                $this->logger->warning(
                    'Not saving {key} to cache as {reason} (id: {id})',
                    [
                        'key'    => $key,
                        'id'     => $id,
                        'reason' => $reason,
                    ]
                );

                $this->writeInProgress = false;

                return false;
            }

            // dump state is used to trigger a full serialized dump in __sleep eg. in Document, AbstractObject
            $data->setInDumpState(false);

            $context = [
                'source' => __METHOD__,
                'conversion' => false,
            ];
            $copier = Service::getDeepCopyInstance($data, $context);
            $copier->addFilter(new SetDumpStateFilter(false), new \DeepCopy\Matcher\PropertyMatcher(ElementDumpStateInterface::class, ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME));

            $copier->prependTypeFilter(new CarbonPeriodFilter(), new TypeMatcher(CarbonPeriodFilter::TYPE));

            $copier->addTypeFilter(
                new \DeepCopy\TypeFilter\ReplaceFilter(
                    function ($currentValue) {
                        if ($currentValue instanceof CacheMarshallerInterface) {
                            return $currentValue->marshalForCache();
                        }

                        return $currentValue;
                    }
                ),
                new TypeMatcher(CacheMarshallerInterface::class)
            );

            $data = $copier->copy($data);
        }

        $item = $this->pool->getItem($key);
        $item->set($data);
        $item->expiresAfter($lifetime);
        $item->tag($tags);
        $result = $this->pool->save($item);

        if ($result) {
            $this->logger->debug('Added entry {key} to cache', ['key' => $item->getKey()]);
        } else {
            try {
                $itemData = $item->get();
                if (!is_scalar($itemData)) {
                    $itemData = serialize($itemData);
                }
                $itemSizeText = FileSystemHelper::formatBytes(mb_strlen((string) $itemData));
            } catch (Throwable) {
                $itemSizeText = 'unknown';
            }

            $this->logger->error(
                'Failed to add entry {key} to cache. Item size was {itemSize}',
                [
                    'key' => $item->getKey(),
                    'itemSize' => $itemSizeText,
                ]
            );
        }

        $this->writeInProgress = false;

        return $result;
    }

    /**
     * Remove a cache item
     */
    public function remove(string $key): bool
    {
        CacheItem::validateKey($key);

        unset($this->prefetchedItems[$key]);

        $this->writeLock->lock();

        return $this->pool->deleteItem($key);
    }

    /**
     * Empty the cache
     */
    public function clearAll(): bool
    {
        $this->prefetchedItems = [];

        $this->writeLock->lock();

        $this->logger->info('Clearing the whole cache');

        $result = $this->pool->clear();

        // immediately acquire the write lock again (force), because the lock is in the cache too
        $this->writeLock->lock(true);

        // set state to cache cleared - prevents new items being written to cache
        $this->cacheCleared = true;

        return $result;
    }

    public function clearTag(string $tag): bool
    {
        return $this->clearTags([$tag]);
    }

    /**
     * @param string[] $tags
     */
    public function clearTags(array $tags): bool
    {
        // the tag-to-key mapping is unknown here, so conservatively drop all
        // prefetched entries
        $this->prefetchedItems = [];

        $this->writeLock->lock();

        $originalTags = $tags;

        $this->logger->debug(
            'Clearing cache tags',
            ['tags' => $tags]
        );

        $tags = $this->normalizeClearTags($tags);
        if (count($tags) > 0) {
            $result = $this->pool->invalidateTags($tags);
            $this->addClearedTags($tags);

            return $result;
        }

        $this->logger->warning(
            'Could not clear tags as tag list is empty after normalization',
            [
                'tags' => $tags,
                'originalTags' => $originalTags,
            ]
        );

        return false;
    }

    /**
     * Clears all tags stored in tagsClearedOnShutdown, this function is executed during OpenDxp shutdown
     */
    public function clearTagsOnShutdown(): bool
    {
        if ($this->tagsClearedOnShutdown === []) {
            return true;
        }

        $this->prefetchedItems = [];

        $this->logger->debug('Clearing shutdown cache tags', ['tags' => $this->tagsClearedOnShutdown]);

        $result = $this->pool->invalidateTags($this->tagsClearedOnShutdown);
        $this->addClearedTags($this->tagsClearedOnShutdown);
        $this->tagsClearedOnShutdown = [];

        return $result;
    }

    /**
     * Normalize (unique) clear tags and shift special tags to shutdown (e.g. output)
     *
     * @param string[] $tags
     *
     * @return string[]
     */
    protected function normalizeClearTags(array $tags): array
    {
        $blocklist = array_flip($this->tagsIgnoredOnClear);

        // Shutdown tags are special tags being shifted to shutdown when scheduled to clear via clearTags. Explanation for
        // the "output" tag:
        // check for the tag output, because items with this tags are only cleared after the process is finished
        // the reason is that eg. long running importers will clean the output-cache on every save/update, that's not necessary,
        // only cleaning the output-cache on shutdown should be enough
        foreach ($this->shutdownTags as $shutdownTag) {
            if (in_array($shutdownTag, $tags)) {
                $this->addTagClearedOnShutdown($shutdownTag);
                $blocklist[$shutdownTag] = true;
            }
        }

        // ensure that every tag is unique
        $tags = array_unique($tags);

        // don't clear tags in ignore array
        $tags = array_filter($tags, fn ($tag) => !isset($blocklist[$tag]));

        return $tags;
    }

    /**
     * Add tag to list of cleared tags (internal use only)
     *
     *
     * @return $this
     */
    protected function addClearedTags(array|string $tags): static
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            $this->clearedTags[$tag] = true;
        }

        return $this;
    }

    /**
     * Adds a tag to the shutdown queue, see clearTagsOnShutdown
     *
     *
     * @return $this
     *
     * @internal
     */
    public function addTagClearedOnShutdown(string $tag): static
    {
        $this->writeLock->lock();

        $this->tagsClearedOnShutdown[] = $tag;
        $this->tagsClearedOnShutdown = array_unique($this->tagsClearedOnShutdown);

        return $this;
    }

    /**
     * @return $this
     *
     * @internal
     */
    public function addTagIgnoredOnSave(string $tag): static
    {
        $this->tagsIgnoredOnSave[] = $tag;
        $this->tagsIgnoredOnSave = array_unique($this->tagsIgnoredOnSave);

        return $this;
    }

    /**
     * @return $this
     *
     * @internal
     */
    public function removeTagIgnoredOnSave(string $tag): static
    {
        $this->tagsIgnoredOnSave = array_filter($this->tagsIgnoredOnSave, fn ($t) => $t !== $tag);

        return $this;
    }

    /**
     * @return $this
     *
     * @internal
     */
    public function addTagIgnoredOnClear(string $tag): static
    {
        $this->tagsIgnoredOnClear[] = $tag;
        $this->tagsIgnoredOnClear = array_unique($this->tagsIgnoredOnClear);

        return $this;
    }

    /**
     * @return $this
     *
     * @internal
     */
    public function removeTagIgnoredOnClear(string $tag): static
    {
        $this->tagsIgnoredOnClear = array_filter($this->tagsIgnoredOnClear, fn ($t) => $t !== $tag);

        return $this;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function removeClearedTags(array $tags): static
    {
        foreach ($tags as $tag) {
            unset($this->clearedTags[$tag]);
        }

        return $this;
    }

    /**
     * Writes save queue to the cache
     *
     * @internal
     */
    public function writeSaveQueue(): bool
    {
        $totalResult = true;

        if ($this->writeLock->hasLock()) {
            if (count($this->saveQueue) > 0) {
                $this->logger->debug(
                    'Not writing save queue as there\'s an active write lock. Save queue contains {saveQueueCount} items.',
                    ['saveQueueCount' => count($this->saveQueue)]
                );
            }

            return false;
        }

        $this->cleanupQueue();

        $processedKeys = [];
        foreach ($this->saveQueue as $queueItem) {
            $key = $queueItem->getKey();

            // check if key was already processed and don't save it again
            if (isset($processedKeys[$key])) {
                $this->logger->warning('Not writing item as key {key} was already processed', ['key' => $key]);

                continue;
            }

            $tags = $this->prepareCacheTags($queueItem->getKey(), $queueItem->getData(), $queueItem->getTags());
            if (null === $tags) {
                $result = false;
                // item shouldn't go to the cache (either because it's tags are ignored or were cleared within this process) -> see $this->prepareCacheTags();
            } else {
                $result = $this->storeCacheData($queueItem->getKey(), $queueItem->getData(), $tags, $queueItem->getLifetime(), $queueItem->isForce());
            }

            $processedKeys[$key] = true;
            $totalResult = $totalResult && $result;
        }

        // reset
        $this->saveQueue = [];

        return $totalResult;
    }

    /**
     * Shut down opendxp - write cache entries and clean up
     *
     *
     * @return $this
     *
     * @internal
     */
    public function shutdown(bool $forceWrite = false): static
    {
        // clear tags scheduled for the shutdown
        $this->clearTagsOnShutdown();

        $doWrite = true;

        // writes make only sense for HTTP(S)
        // CLI are normally longer running scripts that tend to produce race conditions
        // so CLI scripts are not writing to the cache at all
        if ($this->isCli() && (!$this->handleCli && !$forceWrite)) {
            $doWrite = false;
            $queueCount = count($this->saveQueue);
            if ($queueCount > 0) {
                $this->logger->debug(
                    'Not writing save queue to cache as process is running in CLI mode. Save queue contains {saveQueueCount} items.',
                    ['saveQueueCount' => count($this->saveQueue)]
                );
            }
        }

        // write collected items to cache backend
        if ($doWrite) {
            $this->writeSaveQueue();
        }

        // remove the write lock
        $this->writeLock->removeLock();

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }
}
