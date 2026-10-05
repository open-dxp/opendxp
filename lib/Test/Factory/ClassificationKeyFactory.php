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

use OpenDxp\Model\DataObject\ClassDefinition\Data\Input;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;

/**
 * @extends AbstractSavingFactory<KeyConfig>
 *
 * @method KeyConfig create(array|callable $attributes = [])
 * @method static KeyConfig createOne(array $attributes = [])
 * @method static list<KeyConfig> createMany(int $number, array $attributes = [])
 */
final class ClassificationKeyFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return KeyConfig::class;
    }

    protected function defaults(): array
    {
        return [
            'type' => 'input',
            'definition' => json_encode(new Input()),
            'enabled' => true,
        ];
    }
}
