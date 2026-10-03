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

use OpenDxp\Model\DataObject\UnittestBlock;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

/**
 * @extends AbstractDataObjectFactory<UnittestBlock>
 *
 * @method UnittestBlock create(array|callable $attributes = [])
 * @method static UnittestBlock createOne(array $attributes = [])
 * @method static list<UnittestBlock> createMany(int $number, array $attributes = [])
 */
final class BlockObjectFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return UnittestBlock::class;
    }
}
