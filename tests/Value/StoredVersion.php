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
 * A version row together with the data the database adapter writes beside it.
 */
final readonly class StoredVersion
{
    public function __construct(
        public string $storageType,
        public ?int $binaryFileId,
        public ?string $metaData,
        public ?string $binaryData,
    ) {
    }
}
