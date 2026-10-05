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


use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Cache\Core\WriteLock;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;

function cacheHandler(TagAwareAdapter $pool): CoreCacheHandler
{
    $lock = new WriteLock($pool);
    $lock->setLogger(new NullLogger());

    $handler = new CoreCacheHandler($pool, $lock, new EventDispatcher());
    $handler->setLogger(new NullLogger());
    $handler->setHandleCli(true);
    $handler->setForceImmediateWrite(true);

    return $handler;
}
