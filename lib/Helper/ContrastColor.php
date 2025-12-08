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

namespace OpenDxp\Helper;

/**
 * @internal
 */
class ContrastColor
{
    /**
     * returns either hex code of black or white depending on the contrast to the given color
     *
     *
     */
    public static function getContrastColor(string $hexColor): string
    {
        //////////// hexColor RGB
        $R1 = hexdec(substr($hexColor, 1, 2));
        $G1 = hexdec(substr($hexColor, 3, 2));
        $B1 = hexdec(substr($hexColor, 5, 2));

        //////////// Black RGB
        $blackColor = '#000000';
        $R2BlackColor = hexdec(substr($blackColor, 1, 2));
        $G2BlackColor = hexdec(substr($blackColor, 3, 2));
        $B2BlackColor = hexdec(substr($blackColor, 5, 2));

        //////////// Calc contrast ratio
        $L1 = 0.2126 * ($R1 / 255) ** 2.2 +
            0.7152 * ($G1 / 255) ** 2.2 +
            0.0722 * ($B1 / 255) ** 2.2;

        $L2 = 0.2126 * ($R2BlackColor / 255) ** 2.2 +
            0.7152 * ($G2BlackColor / 255) ** 2.2 +
            0.0722 * ($B2BlackColor / 255) ** 2.2;
        $contrastRatio = $L1 > $L2 ? (int)(($L1 + 0.05) / ($L2 + 0.05)) : (int)(($L2 + 0.05) / ($L1 + 0.05));

        //////////// If contrast is more than 5, return black color
        if ($contrastRatio > 5) {
            return '#000000';
        }
        //////////// if not, return white color.
        return '#ffffff';
    }
}
