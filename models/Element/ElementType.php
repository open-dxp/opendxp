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

namespace OpenDxp\Model\Element;

/**
 * The fixed vocabulary of element types.
 *
 * This is the single source of truth for every check, whitelist and switch on an element type —
 * the value returned by {@see Service::getElementType()} and stored in the `ctype`/`type` columns
 * of the element-related tables. Storage stays a plain string; the enum is not used in
 * signatures or persisted values.
 *
 * @see Service::getElementType()
 */
enum ElementType: string
{
    case Document = 'document';
    case Asset = 'asset';
    case Object = 'object';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
