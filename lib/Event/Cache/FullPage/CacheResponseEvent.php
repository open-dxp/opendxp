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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Event\Cache\FullPage;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Determines if a response can be cached.
 */
class CacheResponseEvent extends Event
{
    public function __construct(private readonly Response $response, private bool $cache)
    {
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getCache(): bool
    {
        return $this->cache;
    }

    public function setCache(bool $cache): void
    {
        $this->cache = $cache;
    }
}
