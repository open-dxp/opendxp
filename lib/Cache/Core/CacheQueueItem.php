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

namespace OpenDxp\Cache\Core;

use DateInterval;

/**
 * @internal
 */
class CacheQueueItem
{
    protected int $priority = 0;

    /**
     * @param string[] $tags
     */
    public function __construct(protected string $key, protected mixed $data, protected array $tags = [], /**
     * @param int|DateInterval|null $lifetime
     */
        protected int|null|DateInterval $lifetime = null, ?int $priority = 0, protected bool $force = false)
    {
        $this->priority = (int)$priority;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getLifetime(): DateInterval|int|null
    {
        return $this->lifetime;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}
