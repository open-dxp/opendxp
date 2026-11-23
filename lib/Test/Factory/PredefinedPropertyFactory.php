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

use OpenDxp\Model\Property\Predefined;

/**
 * @extends AbstractSavingFactory<Predefined>
 */
final class PredefinedPropertyFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Predefined::class;
    }

    protected function defaults(): array
    {
        return [
            'name'  => self::faker()->slug(2),
            'key'   => self::faker()->unique()->slug(2),
            'type'  => 'text',
            'ctype' => 'document',
        ];
    }
}
