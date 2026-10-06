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

it('takes out every entry that carries the cleared tag', function (callable $pool, string $tag, array $kept) {
    $this->useCachePool($pool);
    $this->queueSampleEntries();
    $this->handler->writeSaveQueue();

    $this->handler->clearTag($tag);

    expect($this->keptEntries())->toBe($kept);
})->with('cache pools')->with([
    'a tag of A alone' => [
        'tag_a',
        [
            'B',
            'C',
        ],
    ],
    'a tag of B alone' => [
        'tag_b',
        [
            'A',
            'C',
        ],
    ],
    'a tag of C alone' => [
        'tag_c',
        [
            'A',
            'B',
        ],
    ],
    'a tag of A and B' => [
        'tag_ab',
        ['C'],
    ],
    'a tag of B and C' => [
        'tag_bc',
        ['A'],
    ],
    'a tag of every entry' => [
        'tag_all',
        [],
    ],
]);

it('takes out every entry that carries one of the cleared tags', function (callable $pool, array $tags, array $kept) {
    $this->useCachePool($pool);
    $this->queueSampleEntries();
    $this->handler->writeSaveQueue();

    $this->handler->clearTags($tags);

    expect($this->keptEntries())->toBe($kept);
})->with('cache pools')->with([
    'the tags of A alone and of B alone' => [
        [
            'tag_a',
            'tag_b',
        ],
        ['C'],
    ],
    'the tags of A alone and of C alone' => [
        [
            'tag_a',
            'tag_c',
        ],
        ['B'],
    ],
    'the tags of B alone and of C alone' => [
        [
            'tag_b',
            'tag_c',
        ],
        ['A'],
    ],
    'the tags of A and B and of B and C' => [
        [
            'tag_ab',
            'tag_bc',
        ],
        [],
    ],
    'the tags of A alone and of B and C' => [
        [
            'tag_a',
            'tag_bc',
        ],
        [],
    ],
    'the tags of C alone and of A and B' => [
        [
            'tag_c',
            'tag_ab',
        ],
        [],
    ],
]);

it('keeps a new entry under a cleared tag out of the cache', function (callable $pool) {
    $this->useCachePool($pool);
    $this->handler->setForceImmediateWrite(true);
    $this->handler->clearTag('tag_a');

    $saved = $this->handler->save('itemA', 'test', ['tag_a']);

    expect($saved)
        ->toBeFalse()
        ->and($this->poolHasItem('itemA'))
        ->toBeFalse();
})->with('cache pools');

it('keeps an entry under the output tag when the tag is cleared', function (callable $pool) {
    $this->useCachePool($pool);
    $this->handler->setForceImmediateWrite(true);
    $this->handler->save('itemA', 'test', ['output']);

    $this->handler->clearTag('output');

    expect($this->poolHasItem('itemA'))->toBeTrue();
})->with('cache pools');

it('takes an entry under a cleared output tag out at the shutdown', function (callable $pool) {
    $this->useCachePool($pool);
    $this->handler->setForceImmediateWrite(true);
    $this->handler->save('itemA', 'test', ['output']);
    $this->handler->clearTag('output');

    $this->handler->clearTagsOnShutdown();

    expect($this->poolHasItem('itemA'))->toBeFalse();
})->with('cache pools');

describe('a tag held for the shutdown', function () {
    it('takes its entries out when the held tags are cleared', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setForceImmediateWrite(true);
        $this->handler->save('itemA', 'test', ['tag_a']);
        $this->handler->addTagClearedOnShutdown('tag_a');

        $this->handler->clearTagsOnShutdown();

        expect($this->poolHasItem('itemA'))->toBeFalse();
    });

    it('takes its entries out on shutdown', function (callable $pool) {
        $this->useCachePool($pool);
        $this->handler->setForceImmediateWrite(true);
        $this->handler->save('itemA', 'test', ['tag_a']);
        $this->handler->addTagClearedOnShutdown('tag_a');

        $this->handler->shutdown();

        expect($this->poolHasItem('itemA'))->toBeFalse();
    });
})->with('cache pools');
