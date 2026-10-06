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

namespace OpenDxp\Tests\Value;

/**
 * The backend search answers with one page of paths and the total of all results.
 */
final readonly class SearchAnswer
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        public array $paths,
        public int $total,
    ) {
    }
}
