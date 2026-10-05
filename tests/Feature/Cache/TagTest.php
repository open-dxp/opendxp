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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Feature\Cache;

describe('clearing a tag', function () {
    it('takes out every entry carrying it', function (callable $pool, string $tag, array $kept) {

        $this->useCachePool($pool);
        $this->queueSampleEntries();
        $this->handler->writeSaveQueue();

        $this->handler->clearTag($tag);

        expect($this->keptEntries())->toBe($kept);
    });
})->with('cache pools')->with([
    ['tag_a', ['B', 'C']],
    ['tag_b', ['A', 'C']],
    ['tag_c', ['A', 'B']],
    ['tag_ab', ['C']],
    ['tag_bc', ['A']],
    ['tag_all', []],
]);

describe('clearing several tags', function () {
    it('takes out every entry carrying any of them', function (callable $pool, array $tags, array $kept) {

        $this->useCachePool($pool);
        $this->queueSampleEntries();
        $this->handler->writeSaveQueue();

        $this->handler->clearTags($tags);

        expect($this->keptEntries())->toBe($kept);
    });
})->with('cache pools')->with([
    [['tag_a', 'tag_b'], ['C']],
    [['tag_a', 'tag_c'], ['B']],
    [['tag_b', 'tag_c'], ['A']],
    [['tag_ab', 'tag_bc'], []],
    [['tag_a', 'tag_bc'], []],
    [['tag_c', 'tag_ab'], []],
]);

describe('the lists a cleared tag lands on', function () {
    it('names every tag that was cleared', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->handlerProperty('clearedTags'))->toBeEmpty();

        $this->handler->clearTags(['tag_a', 'tag_b', 'output']);

        expect($this->handlerProperty('clearedTags'))->toBe(['tag_a' => true, 'tag_b' => true]);
    });

    it('holds an output tag back for the shutdown', function (callable $pool) {

        $this->useCachePool($pool);

        expect($this->handlerProperty('tagsClearedOnShutdown'))->toBeEmpty();

        $this->handler->clearTags(['tag_a', 'tag_b', 'output']);

        expect($this->handlerProperty('tagsClearedOnShutdown'))->toBe(['output']);

        $this->handler->clearTagsOnShutdown();

        expect($this->handlerProperty('clearedTags'))
            ->toBe(['tag_a' => true, 'tag_b' => true, 'output' => true]);
    });

    it('is worked off when the shutdown clear is called', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->addTagClearedOnShutdown('foo');

        expect($this->handlerProperty('tagsClearedOnShutdown'))->toBe(['foo']);

        $this->handler->clearTagsOnShutdown();

        expect($this->handlerProperty('clearedTags'))->toBe(['foo' => true]);
    });

    it('is worked off on shutdown', function (callable $pool) {

        $this->useCachePool($pool);
        $this->handler->addTagClearedOnShutdown('foo');

        expect($this->handlerProperty('tagsClearedOnShutdown'))->toBe(['foo']);

        $this->handler->shutdown();

        expect($this->handlerProperty('clearedTags'))->toBe(['foo' => true]);
    });
})->with('cache pools');
