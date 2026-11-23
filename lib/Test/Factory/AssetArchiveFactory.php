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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset\Archive;

use function Zenstruck\Foundry\lazy;

/**
 * @extends AbstractElementFactory<Archive>
 */
final class AssetArchiveFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Archive::class;
    }

    public static function fixture(): string
    {
        return dirname(__DIR__) . '/Fixtures/archive.zip';
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'filename' => sprintf('%s.zip', self::faker()->unique()->slug()),
            'data'     => lazy(static fn () => file_get_contents(self::fixture())),
        ];
    }
}
