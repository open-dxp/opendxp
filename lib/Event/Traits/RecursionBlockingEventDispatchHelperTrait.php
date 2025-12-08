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

namespace OpenDxp\Event\Traits;

use OpenDxp;

/**
 * @internal
 */
trait RecursionBlockingEventDispatchHelperTrait
{
    private array $activeDispatchingEvents = [];

    /**
     * Dispatches an event, avoids recursion by checking if the active dispatch event is the same
     *
     *
     */
    protected function dispatchEvent(object $event, ?string $eventName = null): void
    {
        $eventName ??= $event::class;
        if (!isset($this->activeDispatchingEvents[$eventName])) {
            $this->activeDispatchingEvents[$eventName] = true;
            OpenDxp::getEventDispatcher()->dispatch($event, $eventName);
            unset($this->activeDispatchingEvents[$eventName]);
        }
    }
}
