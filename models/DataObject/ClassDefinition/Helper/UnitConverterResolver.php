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

use OpenDxp\Model\DataObject\QuantityValue\QuantityValueConverterInterface;

/**
 * @internal
 */
class UnitConverterResolver extends ClassResolver
{
    public static function resolveUnitConverter(string $converterServiceName): ?QuantityValueConverterInterface
    {
        /** @var QuantityValueConverterInterface $converter */
        $converter = self::resolve('@' . $converterServiceName, static fn($converterService) => $converterService instanceof QuantityValueConverterInterface);

        return $converter;
    }
}
