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
});

it('clears an output tagged entry on shutdown and not before', function () {

    $this->handler->save('outputKey', 'output-data', ['output']);
    $this->handler->clearTags(['output']);

    expect($this->pool->getItem('outputKey')->isHit())->toBeTrue();

    $this->handler->clearTagsOnShutdown();

    expect($this->pool->getItem('outputKey')->isHit())->toBeFalse();
});
