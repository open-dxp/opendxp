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


use OpenDxp\Tests\TestCase\CacheTestCase;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

// Without a namespace, clear() runs TRUNCATE. DDL commits the open transaction.
// With a namespace it runs DELETE.
dataset('cache pools', [
    'doctrine dbal' => [fn () => new TagAwareAdapter(new DoctrineDbalAdapter(
        \OpenDxp::getContainer()->get('doctrine.dbal.default_connection'),
        CacheTestCase::NAMESPACE,
        CacheTestCase::LIFETIME,
    ))],
    'redis' => [fn () => new RedisTagAwareAdapter(
        RedisAdapter::createConnection(getenv('OPENDXP_TEST_REDIS_DSN')),
        CacheTestCase::NAMESPACE,
        CacheTestCase::LIFETIME,
    )],
]);
