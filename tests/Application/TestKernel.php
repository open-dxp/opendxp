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

namespace OpenDxp\Tests\Application;

use OpenDxp\TestFoundation\Kernel\TestKernel as Foundation;

final class TestKernel extends Foundation
{
    /**
     * Core's services keep the names of their classes, so marking the whole OpenDxp namespace
     * would keep every unused definition in the container, including the ones that are not
     * services at all. The test container hands out private services anyway.
     */
    protected function getServicesClass(): string
    {
        return self::class;
    }
}
