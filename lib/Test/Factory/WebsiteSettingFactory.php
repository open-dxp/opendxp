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

use OpenDxp\Model\WebsiteSetting;

/**
 * @extends AbstractSavingFactory<WebsiteSetting>
 *
 * @method WebsiteSetting create(array|callable $attributes = [])
 * @method static WebsiteSetting createOne(array $attributes = [])
 * @method static list<WebsiteSetting> createMany(int $number, array $attributes = [])
 */
final class WebsiteSettingFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return WebsiteSetting::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => sprintf('setting-%s', self::faker()->unique()->numerify('##########')),
            'type' => 'text',
        ];
    }
}
