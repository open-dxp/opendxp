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

use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\TestCase\CacheTestCase;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

function redisDsn(): string
{
    $dsn = getenv('OPENDXP_TEST_REDIS_DSN');

    if (!is_string($dsn) || $dsn === '') {
        test()->markTestSkipped('OPENDXP_TEST_REDIS_DSN names no Redis server.');
    }

    return $dsn;
}

// Without a namespace, clear() runs TRUNCATE. DDL commits the open transaction.
// With a namespace it runs DELETE.
dataset('cache pools', [
    'doctrine dbal' => [
        fn () => new TagAwareAdapter(
            new DoctrineDbalAdapter(
                Container::get('doctrine.dbal.default_connection'),
                CacheTestCase::NAMESPACE,
                CacheTestCase::LIFETIME,
            ),
        ),
    ],
    'redis' => [
        fn () => new RedisTagAwareAdapter(
            RedisAdapter::createConnection(redisDsn()),
            CacheTestCase::NAMESPACE,
            CacheTestCase::LIFETIME,
        ),
    ],
]);
