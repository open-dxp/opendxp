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

namespace OpenDxp\Tests\Factory;

use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

/**
 * @extends AbstractDataObjectFactory<Inheritance>
 *
 * @method Inheritance create(array|callable $attributes = [])
 * @method static Inheritance createOne(array $attributes = [])
 * @method static list<Inheritance> createMany(int $number, array $attributes = [])
 */
final class InheritanceFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return Inheritance::class;
    }
}
