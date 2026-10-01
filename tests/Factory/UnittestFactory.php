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

use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

/**
 * @extends AbstractDataObjectFactory<Unittest>
 *
 * @method Unittest create(array|callable $attributes = [])
 * @method static Unittest createOne(array $attributes = [])
 * @method static list<Unittest> createMany(int $number, array $attributes = [])
 */
final class UnittestFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return Unittest::class;
    }
}
