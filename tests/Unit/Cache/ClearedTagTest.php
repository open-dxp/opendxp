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
    $this->handler = cacheHandler(new TagAwareAdapter(new ArrayAdapter()));
    $this->handler->setForceImmediateWrite(true);
});

it('refuses to cache a tag it cleared', function () {
    $this->handler->clearTags(['cleared_tag']);

    $this->handler->save('refusedKey', 'refused-data', ['cleared_tag']);

    expect($this->handler->load('refusedKey'))->toBeFalse();
});

it('caches a tag again after a reset', function () {
    $this->handler->clearTags(['cleared_tag']);
    $this->handler->reset();

    $this->handler->save('acceptedKey', 'accepted-data', ['cleared_tag']);

    expect($this->handler->load('acceptedKey'))->toBe('accepted-data');
});
