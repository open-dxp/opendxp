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
 */
final class RelationTestFactory extends AbstractDataObjectFactory
{
    /**
     * A test looks for this text in a serialized object to tell whether a marked target was written along with it.
     */
    public const string MARKER = 'the text of a relation target';

    public static function class(): string
    {
        return RelationTest::class;
    }

    public function marked(): static
    {
        return $this->with(['someAttribute' => self::MARKER]);
    }
}
