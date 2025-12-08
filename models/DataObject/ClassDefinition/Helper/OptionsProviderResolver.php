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

namespace OpenDxp\Model\DataObject\ClassDefinition\Helper;

use OpenDxp\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;

/**
 * @internal
 */
class OptionsProviderResolver extends ClassResolver
{
    const MODE_SELECT = 1;

    const MODE_MULTISELECT = 2;

    public static array $providerCache = [];

    public static function resolveProvider(?string $providerClass, int $mode, bool $showError = false): ?object
    {
        return self::resolve($providerClass, fn ($provider) => ($mode === self::MODE_SELECT && ($provider instanceof SelectOptionsProviderInterface))
            || ($mode === self::MODE_MULTISELECT && ($provider instanceof SelectOptionsProviderInterface)), $showError);
    }
}
