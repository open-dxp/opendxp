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

namespace OpenDxp;

use Composer\InstalledVersions;

/**
 * @internal
 */
final class Version
{
    const string PACKAGE_NAME = 'open-dxp/opendxp';

    private const int MAJOR_VERSION = 1;

    public static function getMajorVersion(): int
    {
        return self::MAJOR_VERSION;
    }

    public static function getVersion(): string
    {
        return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);
    }

    public static function getRevision(): string
    {
        return InstalledVersions::getReference(self::PACKAGE_NAME);
    }
}
