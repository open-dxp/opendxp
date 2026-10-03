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

use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;

/**
 * @extends AbstractSavingFactory<GroupConfig>
 *
 * @method GroupConfig create(array|callable $attributes = [])
 * @method static GroupConfig createOne(array $attributes = [])
 * @method static list<GroupConfig> createMany(int $number, array $attributes = [])
 */
final class ClassificationGroupFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return GroupConfig::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}
