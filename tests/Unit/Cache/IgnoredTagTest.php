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

namespace OpenDxp\Tests\Unit\Cache;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

beforeEach(function () {
    $this->pool = new TagAwareAdapter(new ArrayAdapter());
    $this->handler = cacheHandler($this->pool);
    $this->handler->setForceImmediateWrite(true);
});

it('saves an entry whose tags are all allowed', function () {
    $saved = $this->handler->save('plainKey', 'plain-data', ['some_tag']);

    expect($saved)
        ->toBeTrue()
        ->and($this->pool->getItem('plainKey')->isHit())
        ->toBeTrue();
});

it('refuses an entry with a tag that is ignored on save', function () {
    $this->handler->addTagIgnoredOnSave('blocked');

    $saved = $this->handler->save(
        'blockedKey',
        'blocked-data',
        [
            'some_tag',
            'blocked',
        ],
    );

    expect($saved)
        ->toBeFalse()
        ->and($this->pool->getItem('blockedKey')->isHit())
        ->toBeFalse();
});

it('saves an entry again once its tag is allowed on save', function () {
    $this->handler->addTagIgnoredOnSave('blocked');
    $this->handler->removeTagIgnoredOnSave('blocked');

    $saved = $this->handler->save('laterKey', 'later-data', ['blocked']);

    expect($saved)
        ->toBeTrue()
        ->and($this->pool->getItem('laterKey')->isHit())
        ->toBeTrue();
});

it('allows a tag again after one removal', function () {
    $this->handler->addTagIgnoredOnSave('blocked');
    $this->handler->addTagIgnoredOnSave('blocked');
    $this->handler->removeTagIgnoredOnSave('blocked');

    $saved = $this->handler->save('laterKey', 'later-data', ['blocked']);

    expect($saved)->toBeTrue();
});

it('keeps an entry whose tag is ignored on clear', function () {
    $this->handler->save('keptKey', 'kept-data', ['protected']);
    $this->handler->addTagIgnoredOnClear('protected');

    $this->handler->clearTags(['protected']);

    expect($this->pool->getItem('keptKey')->isHit())->toBeTrue();
});

it('clears an entry again once its tag is allowed on clear', function () {
    $this->handler->addTagIgnoredOnClear('protected');
    $this->handler->save('keptKey', 'kept-data', ['protected']);
    $this->handler->removeTagIgnoredOnClear('protected');

    $this->handler->clearTags(['protected']);

    expect($this->pool->getItem('keptKey')->isHit())->toBeFalse();
});
