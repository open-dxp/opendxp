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

namespace OpenDxp\Model\Property;

/**
 * The fixed vocabulary of property types.
 *
 * This is the single source of truth for every check, whitelist and switch on a property type.
 *
 * @see \OpenDxp\Model\Property
 * @see \OpenDxp\Model\WebsiteSetting
 */
enum PropertyType: string
{
    case Text = 'text';
    case Document = 'document';
    case Asset = 'asset';
    case Object = 'object';
    case Bool = 'bool';
    case Select = 'select';

    public function isElementReference(): bool
    {
        return match ($this) {
            self::Document,
            self::Asset,
            self::Object => true,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
