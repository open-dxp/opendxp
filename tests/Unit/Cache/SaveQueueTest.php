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
    $this->handler->setForceImmediateWrite(false);
});

it('writes the last value a key was given', function () {

    $this->handler->save('dup', 'first', []);
    $this->handler->save('dup', 'second', []);
    $this->handler->writeSaveQueue();

    expect($this->pool->getItem('dup')->get())->toBe('second');
});
