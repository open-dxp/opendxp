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

namespace OpenDxp\Tests\PureUnit\Cache\Core;

use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Pins down the behavior of the tag ignore/clear lists in
 * {@see CoreCacheHandler}: adding/removing tags must affect whether items are
 * saved or cleared. The internal representation of these lists may change
 * (e.g. list -> hashmap for O(1) lookup) but the externally observable
 * behavior must stay identical.
 *
 * @group cache.core.tag-ignored
 */
class TagIgnoredBehaviorTest extends \Codeception\Test\Unit
{
    protected TagAwareAdapter $cache;

    protected CoreCacheHandler $handler;

    protected WriteLock $writeLock;

    protected function setUp(): void
    {
        $this->cache = new TagAwareAdapter(new ArrayAdapter());
        $this->cache->clear();
        $this->writeLock = new WriteLock($this->cache);
        $this->writeLock->setLogger(new NullLogger());
        $this->handler = new CoreCacheHandler($this->cache, $this->writeLock, new EventDispatcher());
        $this->handler->setLogger(new NullLogger());
        $this->handler->setHandleCli(true);
        $this->handler->setForceImmediateWrite(true);
    }

    public function testItemSavedNormallyWhenTagIsNotIgnored(): void
    {
        $this->assertTrue($this->handler->save('itemX', 'data', ['tag_x']));
        $this->assertTrue($this->cache->getItem('itemX')->isHit());
    }

    public function testItemNotSavedWhenAnyOfItsTagsIsIgnoredOnSave(): void
    {
        $this->handler->addTagIgnoredOnSave('blocked');

        $result = $this->handler->save('itemBlocked', 'data', ['tag_x', 'blocked']);

        $this->assertFalse($result, 'save() must return false when an ignored-on-save tag is present');
        $this->assertFalse($this->cache->getItem('itemBlocked')->isHit());
    }

    public function testRemoveTagIgnoredOnSaveRestoresSaveBehavior(): void
    {
        $this->handler->addTagIgnoredOnSave('blocked');
        $this->handler->save('itemBefore', 'data', ['blocked']);
        $this->assertFalse($this->cache->getItem('itemBefore')->isHit());

        $this->handler->removeTagIgnoredOnSave('blocked');

        $this->assertTrue($this->handler->save('itemAfter', 'data', ['blocked']));
        $this->assertTrue($this->cache->getItem('itemAfter')->isHit());
    }

    public function testAddingSameTagTwiceRemainsIgnoredAfterSingleRemoval(): void
    {
        // Adding the same tag twice should still leave it ignored after a
        // single removal — the public contract is "is tag ignored or not",
        // not "how many entries does it have".
        $this->handler->addTagIgnoredOnSave('blocked');
        $this->handler->addTagIgnoredOnSave('blocked');
        $this->handler->removeTagIgnoredOnSave('blocked');

        $this->assertTrue($this->handler->save('itemAfter', 'data', ['blocked']));
        $this->assertTrue($this->cache->getItem('itemAfter')->isHit());
    }

    public function testItemNotClearedWhenItsTagIsIgnoredOnClear(): void
    {
        $this->handler->save('itemKeep', 'data', ['protected']);
        $this->assertTrue($this->cache->getItem('itemKeep')->isHit());

        $this->handler->addTagIgnoredOnClear('protected');
        $this->handler->clearTags(['protected']);

        $this->assertTrue(
            $this->cache->getItem('itemKeep')->isHit(),
            'Items with an ignored-on-clear tag must survive clearTags()'
        );
    }

    public function testRemoveTagIgnoredOnClearRestoresClearBehavior(): void
    {
        $this->handler->addTagIgnoredOnClear('protected');
        $this->handler->save('itemA', 'data', ['protected']);
        $this->assertTrue($this->cache->getItem('itemA')->isHit());

        $this->handler->removeTagIgnoredOnClear('protected');
        $this->handler->clearTags(['protected']);

        $this->assertFalse(
            $this->cache->getItem('itemA')->isHit(),
            'After removing the ignored-on-clear entry, clearTags() must clear the item'
        );
    }

    public function testWriteSaveQueueDeduplicatesRepeatedKeys(): void
    {
        // The save queue keeps the latest entry per key. writeSaveQueue() must
        // process each key exactly once even if the same key appears multiple
        // times in the queue (proposal 4 changes this from in_array() scan
        // to a hashmap; the dedup behavior must be preserved).
        $this->handler->setForceImmediateWrite(false);
        $this->handler->save('dup', 'first', []);
        $this->handler->save('dup', 'second', []);

        $this->handler->writeSaveQueue();

        $this->assertTrue($this->cache->getItem('dup')->isHit());
        $this->assertSame('second', $this->cache->getItem('dup')->get());
    }

    public function testShutdownTagsAreNotImmediatelyCleared(): void
    {
        $this->handler->save('outputItem', 'data', ['output']);
        $this->assertTrue($this->cache->getItem('outputItem')->isHit());

        // 'output' is a shutdown tag — clearTags must defer it
        $this->handler->clearTags(['output']);

        $this->assertTrue(
            $this->cache->getItem('outputItem')->isHit(),
            'Output-tagged items must not be cleared until shutdown'
        );

        $this->handler->clearTagsOnShutdown();

        $this->assertFalse(
            $this->cache->getItem('outputItem')->isHit(),
            'After shutdown processing, output-tagged items must be cleared'
        );
    }
}
