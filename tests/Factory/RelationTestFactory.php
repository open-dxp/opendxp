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

use OpenDxp\Model\DataObject\RelationTest;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

/**
 * @extends AbstractDataObjectFactory<RelationTest>
 *
 * @method RelationTest create(array|callable $attributes = [])
 * @method static RelationTest createOne(array $attributes = [])
 * @method static list<RelationTest> createMany(int $number, array $attributes = [])
 */
final class RelationTestFactory extends AbstractDataObjectFactory
{
    /**
     * The text every target carries. A test looks for it in a serialized object to tell whether the
     * target was written along with it.
     */
    public const string CONTENT = 'the text of a relation target';

    public static function class(): string
    {
        return RelationTest::class;
    }

    protected function defaults(): array
    {
        return [...parent::defaults(), 'someAttribute' => self::CONTENT];
    }
}
