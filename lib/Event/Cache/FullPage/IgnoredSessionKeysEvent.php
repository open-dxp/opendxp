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

use Symfony\Contracts\EventDispatcher\Event;

class IgnoredSessionKeysEvent extends Event
{
    /**
     * @param string[] $keys
     */
    public function __construct(
        /**
         * Session keys which will be ignored when determining
         * if the full page cache should be disabled due to
         * existing session data.
         */
        private array $keys = []
    ) {
    }

    /**
     * @return string[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * @param string[] $keys
     */
    public function setKeys(array $keys): void
    {
        $this->keys = $keys;
    }
}
