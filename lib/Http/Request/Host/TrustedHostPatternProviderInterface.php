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

namespace OpenDxp\Http\Request\Host;

interface TrustedHostPatternProviderInterface
{
    /**
     * Returns more patterns in the form of `framework.trusted_hosts`.
     *
     * Each pattern of a known host starts with ^ and ends with $.
     *
     * @param list<string> $knownHostPatterns
     *
     * @return list<string>
     */
    public function getPatterns(array $knownHostPatterns): array;
}
