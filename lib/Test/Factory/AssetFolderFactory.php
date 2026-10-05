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

use OpenDxp\Model\Asset\Folder;

/**
 * @extends AbstractElementFactory<Folder>
 *
 * @method Folder create(array|callable $attributes = [])
 * @method static Folder createOne(array $attributes = [])
 * @method static list<Folder> createMany(int $number, array $attributes = [])
 */
final class AssetFolderFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Folder::class;
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'filename' => sprintf('asset-folder-%s', uniqid()),
            'type'     => 'folder',
        ];
    }
}
