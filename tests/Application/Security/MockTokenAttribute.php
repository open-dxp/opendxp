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

namespace OpenDxp\Tests\Application\Security;

/**
 * Reports a deprecation when it is unserialized, like the debug class loader does for a class it loads.
 */
final class MockTokenAttribute
{
    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
    {
        trigger_error('A class loaded while the token is unserialized is deprecated.', E_USER_DEPRECATED);
    }
}
