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

namespace OpenDxp\Bundle\InstallBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
class InstallerStepEvent extends Event
{
    public function __construct(private readonly string $type, private readonly string $message, private readonly int $step, private readonly int $totalSteps)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function getTotalSteps(): int
    {
        return $this->totalSteps;
    }
}
