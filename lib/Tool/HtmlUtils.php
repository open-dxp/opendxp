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

namespace OpenDxp\Tool;

/**
 * @internal
 */
class HtmlUtils
{
    /**
     * Builds an attribute string from an array of attributes
     *
     *
     */
    public static function assembleAttributeString(array $attributes, bool $omitNullValues = false): string
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            // do not output null values or use an attribute without
            // value depending on parameter
            if (null === $value) {
                if ($omitNullValues) {
                    continue;
                }
                $parts[] = $key;
            } else {
                $parts[] = sprintf('%s="%s"', $key, $value);
            }
        }

        return implode(' ', $parts);
    }
}
